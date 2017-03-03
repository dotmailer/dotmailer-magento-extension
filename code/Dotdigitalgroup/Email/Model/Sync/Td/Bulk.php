<?php

class Dotdigitalgroup_Email_Model_Sync_Td_Bulk extends Dotdigitalgroup_Email_Model_Sync_Contact_Bulk
{
    /**
     * @param $collection
     */
    public function processCollection($collection)
    {
        foreach ($collection as $item) {
            $websiteId = $item->getWebsiteId();
            $this->client = $this->helper->getWebsiteApiClient($websiteId);
            //@codingStandardsIgnoreStart
            $importData = unserialize($item->getImportData());
            //@codingStandardsIgnoreEnd
            if ($this->client) {
                if (strpos($item->getImportType(), 'Catalog_') !== false) {
                    $result = $this->client->postAccountTransactionalDataImport($importData, $item->getImportType());
                    $this->_handleItemAfterSync($item, $result);
                } else {
                    $result = $this->client->postContactsTransactionalDataImport($importData, $item->getImportType());
                    $this->_handleItemAfterSync($item, $result);
                }
            }
        }
    }
}