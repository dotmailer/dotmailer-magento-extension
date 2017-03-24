<?php

class Dotdigitalgroup_Email_Model_Sync_Td_Delete extends Dotdigitalgroup_Email_Model_Sync_Contact_Delete
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
                $result = $this->client->deleteContactsTransactionalData($importData[0], $item->getImportType());
                $this->_handleSingleItemAfterSync($item, $result);
            }
        }
    }
}