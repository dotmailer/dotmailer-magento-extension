<?php

class Dotdigitalgroup_Email_Model_Sync_Td_Update extends Dotdigitalgroup_Email_Model_Sync_Contact_Delete
{
    public function processCollection($collection)
    {
        foreach($collection as $item)
        {
            $websiteId = $item->getWebsiteId();
            $this->_client = $this->_helper->getWebsiteApiClient($websiteId);
            $importData = unserialize($item->getImportData());

            if ($this->_client) {
                if (strpos($item->getImportType(), 'Catalog_') !== false){
                    $result = $this->_client->postContactsTransactionalData($importData, $item->getImportType(), true);
                }
                else{
                    $result = $this->_client->postContactsTransactionalData($importData, $item->getImportType());
                }

                $this->_handleSingleItemAfterSync($item, $result);
            }
        }
    }
}