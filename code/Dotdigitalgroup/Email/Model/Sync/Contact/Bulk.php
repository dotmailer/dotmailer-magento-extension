<?php

class Dotdigitalgroup_Email_Model_Sync_Contact_Bulk
{
    /**
     * @var Dotdigitalgroup_Email_Helper_Data|Mage_Core_Helper_Abstract
     */
    public $helper;
    /**
     * @var
     */
    public $client;

    /**
     * Dotdigitalgroup_Email_Model_Sync_Contact_Bulk constructor.
     */
    public function __construct()
    {
        $this->helper = Mage::helper('ddg');
    }

    /**
     * @param $collection
     */
    public function processCollection($collection)
    {
        foreach ($collection as $item) {
            $addressBook = '';
            $websiteId = $item->getWebsiteId();
            $this->client = $this->helper->getWebsiteApiClient($websiteId);
            if ($this->client) {
                // Registered customer
                if ($item->getImportType() == Dotdigitalgroup_Email_Model_Importer::IMPORT_TYPE_CONTACT)
                    $addressBook = $this->helper->getCustomerAddressBook($websiteId);
                // Subscriber
                if ($item->getImportType() == Dotdigitalgroup_Email_Model_Importer::IMPORT_TYPE_SUBSCRIBERS)
                    $addressBook = $this->helper->getSubscriberAddressBook($websiteId);
                // Guest customer
                if ($item->getImportType() == Dotdigitalgroup_Email_Model_Importer::IMPORT_TYPE_GUEST)
                    $addressBook = $this->helper->getGuestAddressBook($websiteId);

                $file = $item->getImportFile();
                if (!empty($file) && !empty($addressBook) && $this->client) {
                    if (Mage::helper('ddg/file')->isFilePathExistWithFallback($file)) {
                        $result = $this->client->postAddressBookContactsImport($file, $addressBook);
                        $this->_handleItemAfterSync($item, $result);
                    } else {
                        $item->setImportStatus(Dotdigitalgroup_Email_Model_Importer::FAILED)
                            ->setMessage($this->helper->__('CSV file does not exist in email or archive folder.'))
                            ->save();
                    }
                }
            }
        }
    }

    /**
     * @param $item
     * @param $result
     */
    protected function _handleItemAfterSync($item, $result)
    {
        $curlError = $this->_checkCurlError($item);

        if (!$curlError) {
            if (isset($result->message) && !isset($result->id)) {
                $item->setImportStatus(Dotdigitalgroup_Email_Model_Importer::FAILED)
                    ->setMessage($result->message);

                $item->save();
            } elseif (isset($result->id) && !isset($result->message)) {
                $item->setImportStatus(Dotdigitalgroup_Email_Model_Importer::IMPORTING)
                    ->setImportId($result->id)
                    ->setImportStarted(Mage::getSingleton('core/date')->gmtDate())
                    ->setMessage('')
                    ->save();
            } else {
                $message = (isset($result->message)) ? $result->message : 'Error unknown';

                // Requeue imports if import limit has been exceeded
                if (strpos($message, 'ERROR_IMPORT_TOOMANYACTIVEIMPORTS') !== false) {
                    $item->setImportStatus(Dotdigitalgroup_Email_Model_Importer::NOT_IMPORTED);
                } else {
                    $item->setImportStatus(Dotdigitalgroup_Email_Model_Importer::FAILED);
                }

                $item->setMessage($message);

                //If result id
                if (isset($result->id)) {
                    $item->setImportId($result->id);
                }

                $item->save();
            }
        }
    }

    /**
     * @param $item
     * @return bool
     */
    protected function _checkCurlError($item)
    {
        //if curl error 28
        $curlError = $this->client->getCurlError();
        if ($curlError) {
            $item->setMessage($curlError)
                ->setImportStatus(Dotdigitalgroup_Email_Model_Importer::FAILED)
                ->save();

            return true;
        }

        return false;
    }
}