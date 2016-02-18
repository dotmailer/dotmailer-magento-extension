<?php

class Dotdigitalgroup_Email_Model_Importer extends Mage_Core_Model_Abstract
{
    //import statuses
    const NOT_IMPORTED = 0;
    const IMPORTING = 1;
    const IMPORTED = 2;
    const FAILED = 3;

    //import mode
    const MODE_BULK = 'Bulk';
    const MODE_SINGLE = 'Single';
    const MODE_SINGLE_DELETE = 'Single_Delete';
    const MODE_CONTACT_DELETE = 'Contact_Delete';
    const MODE_CONTACT_EMAIL_UPDATE = 'Contact_Email_Update';
    const MODE_SUBSCRIBER_UPDATE = 'Subscriber_Update';
    const MODE_SUBSCRIBER_RESUBSCRIBED = 'Subscriber_Resubscribed';

    //import type
    const IMPORT_TYPE_CONTACT = 'Contact';
    const IMPORT_TYPE_ORDERS = 'Orders';
    const IMPORT_TYPE_WISHLIST = 'Wishlist';
    const IMPORT_TYPE_REVIEWS = 'Reviews';
    const IMPORT_TYPE_CATALOG = 'Catalog_Default';
    const IMPORT_TYPE_QUOTE = 'Quote';
    const IMPORT_TYPE_SUBSCRIBERS = 'Subscriber';
    const IMPORT_TYPE_GUEST = 'Guest';
    const IMPORT_TYPE_CONTACT_UPDATE = 'Contact';
    const IMPORT_TYPE_SUBSCRIBER_UPDATE = 'Subscriber';
    const IMPORT_TYPE_SUBSCRIBER_RESUBSCRIBED = 'Subscriber';

    protected $_reasons = array(
        'Globally Suppressed',
        'Blocked',
        'Unsubscribed',
        'Hard Bounced',
        'Isp Complaints',
        'Domain Suppressed',
        'Failures',
        'Invalid Entries',
        'Mail Blocked',
        'Suppressed by you'
    );

    protected $import_statuses = array(
        'RejectedByWatchdog', 'InvalidFileFormat', 'Unknown',
        'Failed', 'ExceedsAllowedContactLimit', 'NotAvailableInThisVersion'
    );

    /**
     * constructor
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('ddg_automation/importer');
    }

    /**
     * @return $this|Mage_Core_Model_Abstract
     */
    protected function _beforeSave()
    {
        parent::_beforeSave();
        $now = Mage::getSingleton('core/date')->gmtDate();
        if ($this->isObjectNew()) {
            $this->setCreatedAt($now);
        } else {
            $this->setUpdatedAt($now);
        }
        return $this;
    }

    /**
     * register import in queue
     *
     * @param $importType
     * @param $importData
     * @param $importMode
     * @param $websiteId
     * @param bool $file
     * @return bool
     */
    public function registerQueue($importType, $importData, $importMode, $websiteId, $file = false)
    {
        try {
            if (!empty($importData))
                $importData = serialize($importData);

            if ($file)
                $this->setImportFile($file);

            $this->setImportType($importType)
                ->setImportData($importData)
                ->setWebsiteId($websiteId)
                ->setImportMode($importMode)
                ->save();

            return true;
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * start point. importer queue processor. check if un-finished import exist.
     *
     * @return bool
     */
    public function processQueue()
    {
        $helper = Mage::helper('ddg');
        $helper->allowResourceFullExecution();
        if ($item = $this->_getQueue(true)) {
            $websiteId = $item->getWebsiteId();
            $enabled = $helper->getWebsiteConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_API_ENABLED, $websiteId);
            if ($enabled) {
                $client = Mage::helper('ddg')->getWebsiteApiClient($websiteId);
                if (
                    $item->getImportType() == self::IMPORT_TYPE_CONTACT or
                    $item->getImportType() == self::IMPORT_TYPE_SUBSCRIBERS or
                    $item->getImportType() == self::IMPORT_TYPE_GUEST

                ) {
                    $response = $client->getContactsImportByImportId($item->getImportId());
                } else {
                    $response = $client->getContactsTransactionalDataImportByImportId($item->getImportId());
                }
                //if curl error 28
                $curlError = $client->getCurlError();
                if ($curlError) {
                    $item->setMessage($curlError)
                        ->save();
                } else {
                    if ($response && !isset($response->message)) {
                        if ($response->status == 'Finished') {
                            $now = Mage::getSingleton('core/date')->gmtDate();
                            $item->setImportStatus(self::IMPORTED)
                                ->setImportFinished($now)
                                ->setMessage('')
                                ->save();

                            if (
                                $item->getImportType() == self::IMPORT_TYPE_CONTACT or
                                $item->getImportType() == self::IMPORT_TYPE_SUBSCRIBERS or
                                $item->getImportType() == self::IMPORT_TYPE_GUEST

                            ){
                                if($item->getImportId())
                                    $this->_processContactImportReportFaults($item->getImportId(), $websiteId);
                            }

                            $this->_processQueue();
                        } elseif (in_array($response->status, $this->import_statuses)) {
                            $item->setImportStatus(self::FAILED)
                                ->setMessage('Import failed with status '.$response->status)
                                ->save();

                            $this->_processQueue();
                        }
                    }
                    if ($response && isset($response->message)) {
                        $item->setImportStatus(self::FAILED)
                            ->setMessage($response->message)
                            ->save();

                        $this->_processQueue();
                    }
                }
            }
        } else {
            $this->_processQueue();
        }
        return true;
    }

    protected function _processContactImportReportFaults($id, $websiteId)
    {
        $helper = Mage::helper('ddg');
        $client = $helper->getWebsiteApiClient($websiteId);
        $data = $client->getContactImportReportFaults($id);

        if ($data) {
            $data = $this->_remove_utf8_bom($data);
            $fileName = Mage::getBaseDir('var') . DS . 'DmTempCsvFromApi.csv';
            $io = new Varien_Io_File();
            $io->open();
            $check = $io->write($fileName, $data);
            if ($check) {
                $csvArray = $this->_csv_to_array($fileName);
                $io->rm($fileName);
                Mage::getResourceModel('ddg_automation/contact')->unsubscribe($csvArray);
            } else {
                $helper->log('_processContactImportReportFaults: cannot save data to CSV file.');
            }
        }
    }

    /**
     * actual importer queue processor
     */
    protected function _processQueue() {
        if ($item = $this->_getQueue()) {
            $helper = Mage::helper('ddg');
            $websiteId = $item->getWebsiteId();
            $client = $helper->getWebsiteApiClient($websiteId);
            $now = Mage::getSingleton('core/date')->gmtDate();
            $error = false;

            if ( //import requires file
                $item->getImportType() == self::IMPORT_TYPE_CONTACT or
                $item->getImportType() == self::IMPORT_TYPE_SUBSCRIBERS or
                $item->getImportType() == self::IMPORT_TYPE_GUEST
            ) {
                if ($item->getImportMode() == self::MODE_CONTACT_DELETE) {
                    //remove from account
                    $client = Mage::helper('ddg')->getWebsiteApiClient($websiteId);
                    $email = unserialize($item->getImportData());
                    $apiContact = $client->postContacts($email);
                    if (!isset($apiContact->message) && isset($apiContact->id)) {
                        $result = $client->deleteContact($apiContact->id);
                        if (isset($result->message)) {
                            $error = true;
                        }
                    } elseif (isset($apiContact->message) && !isset($apiContact->id)) {
                        $error = true;
                        $result = $apiContact;
                    }
                } else {
                    //address book
                    $addressbook = '';
                    if ($item->getImportType() == self::IMPORT_TYPE_CONTACT)
                        $addressbook = $helper->getCustomerAddressBook($websiteId);
                    if ($item->getImportType() == self::IMPORT_TYPE_SUBSCRIBERS)
                        $addressbook = $helper->getSubscriberAddressBook($websiteId);
                    if ($item->getImportType() == self::IMPORT_TYPE_GUEST)
                        $addressbook = $helper->getGuestAddressBook($websiteId);

                    $file = $item->getImportFile();
                    if (!empty($file) && !empty($addressbook)) {
                        $result = $client->postAddressBookContactsImport($file, $addressbook);
                        $fileHelper = Mage::helper('ddg/file');
                        if (isset($result->message) && !isset($result->id))
                            $error = true;
                        elseif(isset($result->id))
                            $fileHelper->archiveCSV($file);
                    }
                }
            } elseif ($item->getImportMode() == self::MODE_SINGLE_DELETE) { //import to single delete
                $importData = unserialize($item->getImportData());
                $result = $client->deleteContactsTransactionalData($importData[0], $item->getImportType());
                if (isset($result->message)) {
                    $error = true;
                }
            } else {
                $importData = unserialize($item->getImportData());
                //catalog type and bulk mode
                if (strpos($item->getImportType(), 'Catalog_') !== false && $item->getImportMode() == self::MODE_BULK) {
                    $result = $client->postAccountTransactionalDataImport($importData, $item->getImportType());
                    if (isset($result->message) && !isset($result->id)) {
                        $error = true;
                    }
                } elseif ($item->getImportMode() == self::MODE_SINGLE) { // single contact import
                    $result = $client->postContactsTransactionalData($importData, $item->getImportType());
                    if (isset($result->message)) {
                        $error = true;
                    }
                } elseif ($item->getImportMode() == self::MODE_CONTACT_EMAIL_UPDATE){
                    $emailBefore = $importData['emailBefore'];
                    $email = $importData['email'];
                    $isSubscribed = $importData['isSubscribed'];
                    $subscribersAddressBook = Mage::helper('ddg')->getWebsiteConfig(
                        Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SUBSCRIBERS_ADDRESS_BOOK_ID, $websiteId);
                    $result = $client->postContacts($emailBefore);
                    //check for matching email
                    if (isset($result->id)) {
                        if ($email != $result->email) {
                            $data = array(
                                'Email' => $email,
                                'EmailType' => 'Html'
                            );
                            //update the contact with same id - different email
                            $client->updateContact($result->id, $data);
                        }
                        if (!$isSubscribed && $result->status == 'Subscribed') {
                            $client->deleteAddressBookContact($subscribersAddressBook, $result->id);
                        }
                    } elseif (isset($result->message)) {
                        $error = true;
                        Mage::helper('ddg')->log('Email change error : ' . $result->message);
                    }
                } elseif ($item->getImportMode() == self::MODE_SUBSCRIBER_UPDATE){
                    $email = $importData['email'];
                    $id = $importData['id'];
                    $contactEmail = Mage::getModel('ddg_automation/contact')->load($id);
                    $result = $client->postContacts( $email );
                    if ( isset( $result->id ) ) {
                        $contactId = $result->id;
                        $client->deleteAddressBookContact( $helper->getSubscriberAddressBook( $websiteId ), $contactId );
                        $contactEmail->setContactId($contactId)
                            ->save();
                    } else {
                        $contactEmail->setSuppressed( '1' )
                            ->save();
                    }
                } elseif ($item->getImportMode() == self::MODE_SUBSCRIBER_RESUBSCRIBED){
                    $email = $importData['email'];
                    $apiContact = $client->postContacts( $email );

                    //resubscribe suppressed contacts
                    if (isset($apiContact->message) && $apiContact->message == Dotdigitalgroup_Email_Model_Apiconnector_Client::API_ERROR_CONTACT_SUPPRESSED) {
                        $apiContact = $client->getContactByEmail($email);
                        $client->postContactsResubscribe( $apiContact );
                    }
                } else { //bulk import transactional data
                    $result = $client->postContactsTransactionalDataImport($importData, $item->getImportType());
                    if (isset($result->message) && !isset($result->id)) {
                        $error = true;
                    }
                }
            }
            //if curl error 28
            $curlError = $client->getCurlError();
            if ($curlError) {
                $item->setMessage($curlError)
                    ->setImportStatus(self::FAILED)
                    ->save();
            } else {
                if (!$error) {
                    if ($item->getImportMode() == self::MODE_SINGLE_DELETE or
                        $item->getImportMode() == self::MODE_SINGLE or
                        $item->getImportMode() == self::MODE_CONTACT_DELETE or
                        $item->getImportMode() == self::MODE_CONTACT_EMAIL_UPDATE or
                        $item->getImportMode() == self::MODE_SUBSCRIBER_RESUBSCRIBED or
                        $item->getImportMode() == self::MODE_SUBSCRIBER_UPDATE
                    ) {
                        $item->setImportStatus(self::IMPORTED)
                            ->setImportFinished($now)
                            ->setImportStarted($now)
                            ->setMessage('')
                                ->save();

                        //process again next item in queue
                        $this->_processQueue();
                    } elseif (isset($result->id) && !isset($result->message)) {
                        $item->setImportStatus(self::IMPORTING)
                            ->setImportId($result->id)
                            ->setImportStarted($now)
                            ->setMessage('')
                            ->save();
                    } else {
	                    $message = (isset($result->message))? $result->message : 'Error unknown';
                        $item->setImportStatus(self::FAILED)
                            ->setMessage($message);

                        if(isset($result->id))
                            $item->setImportId($result->id);

                        $item->save();
                    }
                } elseif ($error) {

	                $message = (isset($result->message))? $result->message : 'Error unknown';

	                $item->setImportStatus(self::FAILED)
                            ->setMessage($message);
                    if (isset($result->id))
                        $item->setImportId($result->id);

	                $item->save();
                }
            }
        }
    }

    /**
     * get queue items from importer
     *
     * @param bool $importing
     * @return bool|Varien_Object
     */
    protected function _getQueue($importing = false)
    {
        $collection = $this->getCollection();

        //if true then return item with importing status
        if ($importing)
            $collection->addFieldToFilter('import_status', array('eq' => self::IMPORTING));
        else
            $collection->addFieldToFilter('import_status', array('eq' => self::NOT_IMPORTED));

        $collection->setPageSize(1);
        if ($collection->count()) {
            return $collection->getFirstItem();
        }
        return false;
    }

    protected function _csv_to_array($filename)
    {
        if(!file_exists($filename) || !is_readable($filename))
            return FALSE;

        $header = NULL;
        $data = array();
        if (($handle = fopen($filename, 'r')) !== FALSE) {

            while (($row = fgetcsv($handle)) !== FALSE) {

	            if (!$header)
                    $header = $row;
                else
                    $data[] = array_combine($header, $row);
            }
            fclose($handle);
        }

        $contacts = array();
        foreach ($data as $item) {
            if (in_array($item['Reason'], $this->_reasons))
                $contacts[] = $item['email'];
        }

        return $contacts;
    }

    protected function _remove_utf8_bom($text)
    {
        $bom = pack('H*','EFBBBF');
        $text = preg_replace("/^$bom/", '', $text);
        return $text;
    }
}