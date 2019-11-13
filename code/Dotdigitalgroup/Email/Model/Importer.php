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
    const IMPORT_TYPE_QUOTE = 'Quote';
    const IMPORT_TYPE_SUBSCRIBERS = 'Subscriber';
    const IMPORT_TYPE_GUEST = 'Guest';
    const IMPORT_TYPE_CONTACT_UPDATE = 'Contact';
    const IMPORT_TYPE_SUBSCRIBER_UPDATE = 'Subscriber';
    const IMPORT_TYPE_SUBSCRIBER_RESUBSCRIBED = 'Subscriber';

    //sync limits
    const SYNC_SINGLE_LIMIT_NUMBER = 100;

    /**
     * @var array
     */
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

    /**
     * @var array
     */
    public $importStatuses = array(
        'RejectedByWatchdog', 'InvalidFileFormat', 'Unknown',
        'Failed', 'ExceedsAllowedContactLimit', 'NotAvailableInThisVersion'
    );

    public $bulkPriority;
    public $singlePriority;
    public $totalItems;
    public $bulkSyncLimit;

    /**
     * Constructor.
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
     * Register import in queue.
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

        return false;
    }

    /**
     * @codingStandardsIgnoreStart
     * Check import status.
     */
    protected function _checkImportStatus()
    {
        $helper = Mage::helper('ddg');
        $helper->allowResourceFullExecution();
        if ($items = $this->_getImportingItems($this->bulkSyncLimit)) {
            foreach ($items as $item) {
                $websiteId = $item->getWebsiteId();
                $client = Mage::helper('ddg')->getWebsiteApiClient($websiteId);
                if ($client) {
                    if ($item->getImportType() == self::IMPORT_TYPE_CONTACT or
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
                            ->setImportStatus(self::FAILED)
                            ->save();
                    } else {
                        if ($response && !isset($response->message)) {
                            if ($response->status == 'Finished') {
                                $now = Mage::getSingleton('core/date')->gmtDate();

                                $item->setImportStatus(self::IMPORTED)
                                    ->setImportFinished($now)
                                    ->setMessage('')
                                    ->save();
                                if ($item->getImportType() == self::IMPORT_TYPE_CONTACT or
                                    $item->getImportType() == self::IMPORT_TYPE_SUBSCRIBERS or
                                    $item->getImportType() == self::IMPORT_TYPE_GUEST

                                ) {
                                    if ($file = $item->getImportFile()) {
                                        $fileHelper = Mage::helper('ddg/file');
                                        //remove consent data once imported
                                        $this->cleanProcessedConsent($fileHelper->getFilePath($file));
                                        $fileHelper->archiveCSV($file);
                                    }

                                    if ($item->getImportId()) {
                                        $this->_processContactImportReportFaults($item->getImportId(), $websiteId);
                                    }
                                }
                            } elseif (in_array($response->status, $this->importStatuses)) {
                                $item->setImportStatus(self::FAILED)
                                    ->setMessage('Import failed with status ' . $response->status)
                                    ->save();
                            } else {
                                //Not finished
                                $this->totalItems += 1;
                            }
                        }

                        if ($response && isset($response->message)) {
                            $item->setImportStatus(self::FAILED)
                                ->setMessage($response->message)
                                ->save();
                        }
                    }
                }
            }
        }

        //@codingStandardsIgnoreEnd
    }

    /**
     * @param $id
     * @param $websiteId
     * @throws Exception
     */
    protected function _processContactImportReportFaults($id, $websiteId)
    {
        $helper = Mage::helper('ddg');
        $client = $helper->getWebsiteApiClient($websiteId);
        if ($client instanceof Dotdigitalgroup_Email_Model_Apiconnector_Client) {
            $report = $client->getContactImportReportFaults($id);

            if ($report) {
                $reportData = explode(PHP_EOL, $this->_removeUtf8Bom($report));
                //unset header
                unset($reportData[0]);
                //no data in report
                if (! empty($reportData)) {
                    $contacts = array();
                    foreach ($reportData as $row) {
                        $row = explode(',', $row);
                        //reason
                        if (in_array($row[0], $this->_reasons))
                            //email
                            $contacts[] = $row[1];
                    }

                    //unsubscribe from email contact and newsletter subscriber tables
                    Mage::getResourceModel('ddg_automation/contact')->unsubscribe($contacts);
                }
            }
        }
    }

    /**
     * Proccess the queue data.
     */
    public function processQueue()
    {
        //Set items to 0
        $this->totalItems = 0;

        //Set bulk sync limit
        $this->bulkSyncLimit = 5;

        //Set priority
        $this->_setPriority();

        //Check previous import status
        $this->_checkImportStatus();

        $enabledWebsiteIds = $this->getEnabledWebsiteIds();

        //Bulk priority. Process group 1 first
        foreach ($this->bulkPriority as $bulk) {
            if ($this->totalItems < $bulk['limit']) {
                $collection = $this->_getQueue(
                    $bulk['type'],
                    $bulk['mode'],
                    $bulk['limit'] - $this->totalItems,
                    $enabledWebsiteIds
                );
                if ($collection->getSize()) {
                    $this->totalItems += $collection->getSize();
                    $bulkModel = Mage::getModel($bulk['model']);
                    $bulkModel->processCollection($collection);
                }
            }
        }

        //reset total items to 0
        $this->totalItems = 0;

        //Single/Update priority
        foreach ($this->singlePriority as $single) {
            if ($this->totalItems < $single['limit']) {
                $collection = $this->_getQueue(
                    $single['type'],
                    $single['mode'],
                    $single['limit'] - $this->totalItems,
                    $enabledWebsiteIds
                );
                if ($collection->getSize()) {
                    $this->totalItems += $collection->getSize();
                    $singleModel = Mage::getModel($single['model']);
                    $singleModel->processCollection($collection);
                }
            }
        }

        return array('message' => 'Done.');
    }

    /**
     * Set priority.
     */
    protected function _setPriority()
    {
        /**
         * Bulk
         */
        $defaultBulk = array(
            'model' => '',
            'mode'  => self::MODE_BULK,
            'type'  => '',
            'limit' => $this->bulkSyncLimit
        );

        //Contact Bulk
        $contact = $defaultBulk;
        $contact['model'] = 'ddg_automation/sync_contact_bulk';
        $contact['type'] = array(
            self::IMPORT_TYPE_CONTACT,
            self::IMPORT_TYPE_GUEST,
            self::IMPORT_TYPE_SUBSCRIBERS
        );

        //Bulk Order
        $order = $defaultBulk;
        $order['model'] = 'ddg_automation/sync_td_bulk';
        $order['type'] = self::IMPORT_TYPE_ORDERS;

        //Bulk Quote
        $quote = $defaultBulk;
        $quote['model'] = 'ddg_automation/sync_td_bulk';
        $quote['type'] = self::IMPORT_TYPE_QUOTE;

        //Bulk Other TD
        $other = $defaultBulk;
        $other['model'] = 'ddg_automation/sync_td_bulk';
        $other['type'] = array(
            'Catalog',
            self::IMPORT_TYPE_REVIEWS,
            self::IMPORT_TYPE_WISHLIST
        );

        /**
         * Update
         */
        $defaultSingleUpdate = array(
            'model' => 'ddg_automation/sync_contact_update',
            'mode'  => '',
            'type'  => '',
            'limit' => self::SYNC_SINGLE_LIMIT_NUMBER
        );

        //Subscriber resubscribe
        $subscriberResubscribe = $defaultSingleUpdate;
        $subscriberResubscribe['mode'] = self::MODE_SUBSCRIBER_RESUBSCRIBED;
        $subscriberResubscribe['type'] = self::IMPORT_TYPE_SUBSCRIBER_RESUBSCRIBED;

        //Subscriber update/suppressed
        $subscriberUpdate = $defaultSingleUpdate;
        $subscriberUpdate['mode'] = self::MODE_SUBSCRIBER_UPDATE;
        $subscriberUpdate['type'] = self::IMPORT_TYPE_SUBSCRIBER_UPDATE;

        //Email Change
        $emailChange = $defaultSingleUpdate;
        $emailChange['mode'] = self::MODE_CONTACT_EMAIL_UPDATE;
        $emailChange['type'] = self::IMPORT_TYPE_CONTACT_UPDATE;

        //Order Update
        $orderUpdate = $defaultSingleUpdate;
        $orderUpdate['model'] = 'ddg_automation/sync_td_update';
        $orderUpdate['mode'] = self::MODE_SINGLE;
        $orderUpdate['type'] = self::IMPORT_TYPE_ORDERS;

        //Quote Update
        $quoteUpdate = $defaultSingleUpdate;
        $quoteUpdate['model'] = 'ddg_automation/sync_td_update';
        $quoteUpdate['mode'] = self::MODE_SINGLE;
        $quoteUpdate['type'] = self::IMPORT_TYPE_QUOTE;

        //Update Other TD
        $updateOtherTd = $defaultSingleUpdate;
        $updateOtherTd['model'] = 'ddg_automation/sync_td_update';
        $updateOtherTd['mode'] = self::MODE_SINGLE;
        $updateOtherTd['type'] = array(
            'Catalog',
            self::IMPORT_TYPE_WISHLIST
        );

        /**
         * Delete
         */
        $defaultSingleDelete = array(
            'model' => '',
            'mode'  => '',
            'type'  => '',
            'limit' => self::SYNC_SINGLE_LIMIT_NUMBER
        );

        //Contact Delete
        $contactDelete = $defaultSingleDelete;
        $contactDelete['model'] = 'ddg_automation/sync_contact_delete';
        $contactDelete['mode'] = self::MODE_CONTACT_DELETE;
        $contactDelete['type'] = self::IMPORT_TYPE_CONTACT;

        //TD Delete
        $tdDelete = $defaultSingleDelete;
        $tdDelete['model'] = 'ddg_automation/sync_td_delete';
        $tdDelete['mode']  = self::MODE_SINGLE_DELETE;
        $tdDelete['type']  = array(
            'Catalog',
            self::IMPORT_TYPE_REVIEWS,
            self::IMPORT_TYPE_WISHLIST,
            self::IMPORT_TYPE_ORDERS,
            self::IMPORT_TYPE_QUOTE
        );

        //Bulk Priority
        $this->bulkPriority = array(
            $contact,
            $order,
            $quote,
            $other
        );

        $this->singlePriority = array(
            $subscriberResubscribe,
            $subscriberUpdate,
            $emailChange,
            $orderUpdate,
            $quoteUpdate,
            $updateOtherTd,
            $contactDelete,
            $tdDelete
        );
    }

    /**
     * @param $importType
     * @param $importMode
     * @param $limit
     * @param $enabledWebsiteIds
     *
     * @return Dotdigitalgroup_Email_Model_Resource_Importer_Collection|object
     */
    protected function _getQueue($importType, $importMode, $limit, $enabledWebsiteIds)
    {
        $collection = $this->getCollection();

        if (is_array($importType)) {
            $condition = array();
            foreach ($importType as $type) {
                if($type == 'Catalog')
                    $condition[] = array('like' => $type . '%');
                else
                    $condition[] = array('eq' => $type);
            }

            $collection->addFieldToFilter('import_type', $condition);
        }
        else
            $collection->addFieldToFilter('import_type', array('eq' => $importType));

        $collection->addFieldToFilter('import_mode', array('eq' => $importMode))
            ->addFieldToFilter('import_status', array('eq' => self::NOT_IMPORTED))
            ->addFieldToFilter('website_id', array('in' => $enabledWebsiteIds))
            ->setPageSize($limit)
            ->setCurPage(1);

        return $collection;
    }

    /**
     * @param $limit
     * @return bool|Varien_Data_Collection
     */
    protected function _getImportingItems($limit)
    {
        $collection = $this->getCollection()
            ->addFieldToFilter('import_status', array('eq' => self::IMPORTING))
            ->addFieldToFilter('import_id', array('neq' => ''))
            ->setPageSize($limit)
            ->setCurPage(1);

        if ($collection->getSize())
            return $collection;

        return false;
    }

    /**
     * @param $text
     * @return null|string|string[]
     */
    protected function _removeUtf8Bom($text)
    {
        $bom = pack('H*', 'EFBBBF');
        $text = preg_replace("/^$bom/", '', $text);
        return $text;
    }

    /**
     * @param $file string full path to the csv file.
     */
    protected function cleanProcessedConsent($file)
    {
        try {
            $consentResource = Mage::getResourceModel('ddg_automation/consent');
            $csv = new Varien_File_Csv();
            //read file and get the email addresses
            $index = $csv->getDataPairs($file, 0, 0);
            //remove header data for Email
            unset($index['Email']);
            $emails = array_values($index);
            $result = $consentResource->deleteConsentByEmails($emails);

            if ($count = count($result)) {
                Mage::helper('ddg')->log('Consent data removed : ' . $count);
            }
        }catch(\Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * @return array
     */
    protected function getEnabledWebsiteIds() {
        $enabledWebsiteIds = array();
        $websites        = Mage::app()->getWebsites( true );
        foreach ( $websites as $website ) {
            if ( Mage::helper( 'ddg' )->getWebsiteConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_API_ENABLED,
                $website ) ) {
                $enabledWebsiteIds[] = $website->getId();
            }
        }

        return $enabledWebsiteIds;
    }
}
