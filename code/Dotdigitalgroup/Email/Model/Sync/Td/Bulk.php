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
                    if ($item->getImportType() == Dotdigitalgroup_Email_Model_Importer::IMPORT_TYPE_ORDERS) {
                        //Skip if one hour has not passed from created
                        if ($this->getDateDifference($item->getCreatedAt()) < 3600) {
                            continue;
                        }
                    }

                    $result = $this->client->postContactsTransactionalDataImport($importData, $item->getImportType());
                    $this->_handleItemAfterSync($item, $result);
                }
            }
        }
    }

    /**
     * Get difference between dates
     *
     * @param $created
     * @return false|int
     */
    public function getDateDifference($created)
    {
        $now = Mage::getSingleton('core/date')->gmtDate();
        return strtotime($now) - strtotime($created);
    }
}