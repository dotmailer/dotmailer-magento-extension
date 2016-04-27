<?php

class Dotdigitalgroup_Email_Model_Sync_Contact_Bulk
{
    protected $_helper;
    protected $_client;

    public function __construct()
    {
        $this->_helper = Mage::helper('ddg');
    }

    public function processCollection($collection)
    {
        foreach($collection as $item)
        {
            $addressBook = '';
            $websiteId = $item->getWebsiteId();
            $this->_client = $this->_helper->getWebsiteApiClient($websiteId);
            // Registered customer
            if ($item->getImportType() == Dotdigitalgroup_Email_Model_Importer::IMPORT_TYPE_CONTACT)
                $addressBook = $this->_helper->getCustomerAddressBook($websiteId);
            // Subscriber
            if ($item->getImportType() == Dotdigitalgroup_Email_Model_Importer::IMPORT_TYPE_SUBSCRIBERS)
                $addressBook = $this->_helper->getSubscriberAddressBook($websiteId);
            // Guest customer
            if ($item->getImportType() == Dotdigitalgroup_Email_Model_Importer::IMPORT_TYPE_GUEST)
                $addressBook = $this->_helper->getGuestAddressBook($websiteId);

            $file = $item->getImportFile();
            if (!empty($file) && !empty($addressBook) && $this->_client) {
                $result = $this->_client->postAddressBookContactsImport($file, $addressBook);
                $this->_handleItemAfterSync($item, $result, $file);
            }
        }
    }

    protected function _handleItemAfterSync($item, $result, $file = false)
    {
        $curlError = $this->_checkCurlError($item);
        
        if(!$curlError){
            if (isset($result->message)) {
                //If result id
                if (isset($result->id)) {
                    $item->setImportId($result->id);
                }

                $item->setImportStatus(Dotdigitalgroup_Email_Model_Importer::FAILED)
                    ->setMessage($result->message)
                    ->save();
            } elseif (isset($result->id)) {
                //if file
                if($file){
                    $fileHelper = Mage::helper('ddg/file');
                    $fileHelper->archiveCSV($file);
                }

                //If message
                if (isset($result->message)) {
                    $item->setMessage($result->message);
                }

                $item->setImportStatus(Dotdigitalgroup_Email_Model_Importer::IMPORTING)
                    ->setImportId($result->id)
                    ->setImportStarted(Mage::getSingleton('core/date')->gmtDate())
                    ->save();
            } else {
                $message = (isset($result->message)) ? $result->message : 'Sync failed with no error returned';
                $item->setImportStatus(Dotdigitalgroup_Email_Model_Importer::FAILED)
                    ->setMessage($message);

                //If result id
                if (isset($result->id)) {
                    $item->setImportId($result->id);
                }

                $item->save();
            }
        }
    }

    protected function _checkCurlError($item)
    {
        //if curl error 28
        $curlError = $this->_client->getCurlError();
        if ($curlError) {
            $item->setMessage($curlError)
                ->setImportStatus(Dotdigitalgroup_Email_Model_Importer::FAILED)
                ->save();

            return true;
        }
        return false;
    }
}