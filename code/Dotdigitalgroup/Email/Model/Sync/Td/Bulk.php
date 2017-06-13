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

                    $collectionName = $item->getImportType();
                    $transactionalData = $this->getTransDataForCatalog($importData);
                    $result = $this->client->postAccountTransactionalDataImport($transactionalData, $collectionName);

                    $this->_handleItemAfterSync($item, $result);
                } else {
                    if ($item->getImportType() == Dotdigitalgroup_Email_Model_Importer::IMPORT_TYPE_ORDERS) {
                        //Skip if one hour has not passed from created
                        if (Mage::helper('ddg')->getDateDifference($item->getCreatedAt()) < 3600) {
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
     * @param $importData
     * @return array
     */
    private function getTransDataForCatalog($importData)
    {
        $data = array();
        foreach ($importData as $catalog) {
            if (isset($catalog->id)) {
                $data[] = array(
                    'Key'               => $catalog->id,
                    'ContactIdentifier' => 'account',
                    'Json'              => json_encode($catalog->expose())
                );
            } else {
                $this->helper->log('Catalog trans data with missing id ')
                    ->log($catalog);
            }
        }

        return $data;
    }
}