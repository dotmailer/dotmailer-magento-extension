<?php

class Dotdigitalgroup_Email_Model_Sync_Contact_Delete extends Dotdigitalgroup_Email_Model_Sync_Contact_Bulk
{
    public function processCollection($collection)
    {
        foreach($collection as $item)
        {
            $websiteId = $item->getWebsiteId();
            $email = unserialize($item->getImportData());
            $this->_client = $this->_helper->getWebsiteApiClient($websiteId);
            $result = '';

            if ($this->_client) {
                $apiContact = $this->_client->postContacts($email);
                if (!isset($apiContact->message) && isset($apiContact->id)){
                    $result = $this->_client->deleteContact($apiContact->id);
                }elseif (isset($apiContact->message) && !isset($apiContact->id)){
                    $result = $apiContact;
                }

                if($result){
                    $this->_handleSingleItemAfterSync($item, $result);
                }
            }
        }
    }

    protected function _handleSingleItemAfterSync($item, $result)
    {
        $curlError = $this->_checkCurlError($item);

        if(!$curlError){
            if (isset($result->message) or !$result) {
                $message = (isset($result->message)) ? $result->message : 'Error unknown';
                $item->setImportStatus(Dotdigitalgroup_Email_Model_Importer::FAILED)
                    ->setMessage($message)
                    ->save();
            }else {
                $now = Mage::getSingleton('core/date')->gmtDate();
                $item->setImportStatus(Dotdigitalgroup_Email_Model_Importer::IMPORTED)
                    ->setImportFinished($now)
                    ->setImportStarted($now)
                    ->setMessage('')
                    ->save();
            }
        }
    }
}