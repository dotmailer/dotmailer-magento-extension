<?php

class Dotdigitalgroup_Email_Model_Sync_Td_Delete extends Dotdigitalgroup_Email_Model_Sync_Contact_Delete
{
    public function processCollection($collection)
    {
        foreach($collection as $item)
        {
            $websiteId = $item->getWebsiteId();
            $this->_client = $this->_helper->getWebsiteApiClient($websiteId);
            $importData = unserialize($item->getImportData());

            if ($this->_client) {
                $result = $this->_client->deleteContactsTransactionalData($importData[0], $item->getImportType());
                $this->_handleSingleItemAfterSync($item, $result);
            }
        }
    }
}