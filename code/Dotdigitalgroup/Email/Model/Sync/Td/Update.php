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
                    if ($item->getImportType() == Dotdigitalgroup_Email_Model_Importer::IMPORT_TYPE_ORDERS) {
                        //Skip if one hour has not passed from created
                        if (Mage::helper('ddg')->getDateDifference($item->getCreatedAt()) < 3600) {
                            continue;
                        }
                    }

                    $result = $this->client->postContactsTransactionalData($importData, $item->getImportType());
                }

                $this->_handleSingleItemAfterSync($item, $result);
            }
        }
    }
}