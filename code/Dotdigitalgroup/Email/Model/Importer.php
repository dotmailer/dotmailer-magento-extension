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

    protected $_bulkPriority;
    protected $_singlePriority;
    protected $_totalItems;
    protected $_bulkSyncLimit;

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
        return false;
    }

    protected function _checkImportStatus()
    {
        $helper = Mage::helper('ddg');
        $helper->allowResourceFullExecution();
        if ($items = $this->_getImportingItems($this->_bulkSyncLimit)) {
            foreach($items as $item) {
                $websiteId = $item->getWebsiteId();
                $client = Mage::helper('ddg')->getWebsiteApiClient($websiteId);
                if ($client) {
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
                                if (
                                    $item->getImportType() == self::IMPORT_TYPE_CONTACT or
                                    $item->getImportType() == self::IMPORT_TYPE_SUBSCRIBERS or
                                    $item->getImportType() == self::IMPORT_TYPE_GUEST

                                ) {
                                    if ($item->getImportId()) {
                                        $this->_processContactImportReportFaults($item->getImportId(), $websiteId);
                                    }
                                }
                            } elseif (in_array($response->status, $this->import_statuses)) {
                                $item->setImportStatus(self::FAILED)
                                    ->setMessage('Import failed with status ' . $response->status)
                                    ->save();
                            }else{
                                //Not finished
                                $this->_totalItems += 1;
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
    }

    protected function _processContactImportReportFaults($id, $websiteId)
    {
        $helper = Mage::helper('ddg');
        $client = $helper->getWebsiteApiClient($websiteId);
        $data = $client->getContactImportReportFaults($id);

        if ($data) {
            $data = $this->_removeUtf8Bom($data);
            $fileName = Mage::getBaseDir('var') . DS . 'DmTempCsvFromApi.csv';
            $io = new Varien_Io_File();
            $io->open();
            $check = $io->write($fileName, $data);
            if ($check) {
                $csvArray = $this->_csvToArray($fileName);
                $io->rm($fileName);
                Mage::getResourceModel('ddg_automation/contact')->unsubscribe($csvArray);
            } else {
                $helper->log('_processContactImportReportFaults: cannot save data to CSV file.');
            }
        }
    }

    public function processQueue() {
        //Set items to 0
        $this->_totalItems = 0;

        //Set bulk sync limit
        $this->_bulkSyncLimit = 5;

        //Set priority
        $this->_setPriority();

        //Check previous import status
        $this->_checkImportStatus();

        //Bulk priority. Process group 1 first
        foreach($this->_bulkPriority as $bulk)
        {
            if($this->_totalItems < $bulk['limit'])
            {
                $collection = $this->_getQueue(
                    $bulk['type'],
                    $bulk['mode'],
                    $bulk['limit'] - $this->_totalItems
                );
                if($collection->getSize()){
                    $this->_totalItems += $collection->getSize();
                    $bulkModel = Mage::getModel($bulk['model']);
                    $bulkModel->processCollection($collection);
                }
            }
        }

        //reset total items to 0
        $this->_totalItems = 0;

        //Single/Update priority
        foreach($this->_singlePriority as $single)
        {
            if($this->_totalItems < $single['limit'])
            {
                $collection = $this->_getQueue(
                    $single['type'],
                    $single['mode'],
                    $single['limit'] - $this->_totalItems
                );
                if($collection->getSize()){
                    $this->_totalItems += $collection->getSize();
                    $singleModel = Mage::getModel($single['model']);
                    $singleModel->processCollection($collection);
                }
            }
        }
    }

    protected function _setPriority()
    {
        /**
         * Bulk
         */

        $defaultBulk = array(
            'model' => '',
            'mode'  => self::MODE_BULK,
            'type'  => '',
            'limit' => $this->_bulkSyncLimit
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
        $emailChange['mode'] = self::MODE_SUBSCRIBER_UPDATE;
        $emailChange['type'] = self::IMPORT_TYPE_SUBSCRIBER_UPDATE;

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
        $this->_bulkPriority = array(
            $contact,
            $order,
            $quote,
            $other
        );

        $this->_singlePriority = array(
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

    protected function _getQueue($importType, $importMode, $limit)
    {
        $collection = $this->getCollection();

        if(is_array($importType)){
            $condition = array();
            foreach($importType as $type){
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
            ->setPageSize($limit)
            ->setCurPage(1);

        return $collection;
    }

    protected function _getImportingItems($limit)
    {
        $collection = $this->getCollection()
            ->addFieldToFilter('import_status', array('eq' => self::IMPORTING))
            ->setPageSize($limit)
            ->setCurPage(1);

        if($collection->getSize())
            return $collection;

        return false;
    }

    protected function _csvToArray($filename)
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

    protected function _removeUtf8Bom($text)
    {
        $bom = pack('H*','EFBBBF');
        $text = preg_replace("/^$bom/", '', $text);
        return $text;
    }
}