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
                $item->setImportStatus(Dotdigitalgroup_Email_Model_Importer::FAILED)
                    ->setMessage($result->message);

                $item->save();
            } else {
                //if file
                if($file){
                    $fileHelper = Mage::helper('ddg/file');
                    $fileHelper->archiveCSV($file);
                }

                $item->setImportStatus(Dotdigitalgroup_Email_Model_Importer::IMPORTING)
                    ->setImportId($result->id)
                    ->setImportStarted(Mage::getSingleton('core/date')->gmtDate())
                    ->setMessage('')
                    ->save();
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