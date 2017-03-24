<?php

class Dotdigitalgroup_Email_Model_Sync_Td_Update extends Dotdigitalgroup_Email_Model_Sync_Contact_Delete
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
                    $result = $this->client->postContactsTransactionalData($importData, $item->getImportType(), true);
                } else {
                    $result = $this->client->postContactsTransactionalData($importData, $item->getImportType());
                }

                $this->_handleSingleItemAfterSync($item, $result);
            }
        }
    }
}