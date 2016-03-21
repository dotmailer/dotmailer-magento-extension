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

    public function processQueue() {
        //Set items to 0
        $this->_totalItems = 0;

        //Set bulk sync limit
        $this->_bulkSyncLimit = Mage::helper('ddg')->getWebsiteConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_IMPORTER_BULK_LIMIT
        );

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
                    Mage::getModel($bulk['model'], $collection);
                }
            }
        }

        //Single/Update priority. Process group 2 if nothing from group 1 to process
        if(empty($this->_totalItems)){
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
                        Mage::getModel($single['model'], $collection);
                    }
                }
            }
        }
    }

    protected function _setPriority()
    {
        /**
         * Bulk
         */

        //Bulk Contact
        $contact = array(
            'model' => 'ddg_automation/sync_contact_bulk',
            'mode'  => self::MODE_BULK,
            'type'  => array(
                        self::IMPORT_TYPE_CONTACT,
                        self::IMPORT_TYPE_GUEST,
                        self::IMPORT_TYPE_SUBSCRIBERS
                    ),
            'limit' => $this->_bulkSyncLimit
        );
        $order = $contact;

        //Bulk Order
        $order['model'] = 'ddg_automation/sync_td_bulk';
        $order['type'] = self::IMPORT_TYPE_ORDERS;

        $quote = $other = $order;

        //Bulk Quote
        $quote['type'] = self::IMPORT_TYPE_QUOTE;

        //Bulk Other TD
        $other['type'] = array(
            'Catalog',
            self::IMPORT_TYPE_REVIEWS,
            self::IMPORT_TYPE_WISHLIST
        );

        /**
         * Update
         */

        //Subscriber resubscribe
        $subscriberResubscribe = array(
            'model' => 'ddg_automation/sync_contact_update',
            'mode'  => self::MODE_SUBSCRIBER_RESUBSCRIBED,
            'type'  => self::IMPORT_TYPE_SUBSCRIBER_RESUBSCRIBED,
            'limit' => self::SYNC_SINGLE_LIMIT_NUMBER
        );

        //Email Change
        $emailChange = $subscriberResubscribe;
        $emailChange['mode'] = self::MODE_CONTACT_EMAIL_UPDATE;
        $emailChange['type'] = self::IMPORT_TYPE_CONTACT_UPDATE;

        //Order Update
        $orderUpdate = array(
            'model' => 'ddg_automation/sync_td_update',
            'mode'  => self::MODE_SINGLE,
            'type'  => self::IMPORT_TYPE_ORDERS,
            'limit' => self::SYNC_SINGLE_LIMIT_NUMBER
        );

        //Quote Update
        $quoteUpdate = $orderUpdate;
        $quoteUpdate['type'] = self::IMPORT_TYPE_QUOTE;

        //Update Other TD
        $updateOtherTd = $orderUpdate;
        $updateOtherTd['type'] = array(
            'Catalog',
            self::IMPORT_TYPE_WISHLIST
        );

        /**
         * Delete
         */

        //Contact Delete
        $contactDelete = array(
            'model' => 'ddg_automation/sync_contact_delete',
            'mode'  => self::MODE_CONTACT_DELETE,
            'type'  => self::IMPORT_TYPE_CONTACT,
            'limit' => self::SYNC_SINGLE_LIMIT_NUMBER
        );

        //TD Delete
        $tdDelete = $contactDelete;
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