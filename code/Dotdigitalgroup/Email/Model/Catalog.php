<?php

class Dotdigitalgroup_Email_Model_Catalog extends Mage_Core_Model_Abstract
{

    /**
     * @var mixed
     */
    public $start;
    /**
     * @var int
     */
    public $countProducts = 0;
    /**
     * @var array
     */
    public $productIds = array();

    /**
     * @var array
     */
    private $productsToProcess = array();

    /**
     * Constructor.
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('ddg_automation/catalog');
    }

    /**
     * @return $this|Mage_Core_Model_Abstract
     */
    protected function _beforeSave()
    {
        parent::_beforeSave();
        $now = Mage::getSingleton('core/date')->gmtDate();
        if ($this->isObjectNew()) {
            $this->setCreatedAt($now);
        } else {
            $this->setUpdatedAt($now);
        }

        return $this;
    }

    /**
     * Catalog sync.
     *
     * @return array
     */
    public function sync()
    {
        $response     = array('success' => true, 'message' => '');
        $helper       = Mage::helper('ddg');
        $this->start = microtime(true);
        $importer     = Mage::getModel('ddg_automation/importer');

        //resource allocation
        $helper->allowResourceFullExecution();
        $enabled = Mage::getStoreConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_API_ENABLED
        );

        $sync    = Mage::getStoreConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_CATALOG_ENABLED
        );

        if ($enabled && $sync) {

            $this->removeOrphanedProducts();

            $scope = Mage::getStoreConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_CATALOG_VALUES
            );

            //if only to pull default value
            if ($scope == 1) {
                $products = $this->_exportCatalog(
                    Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID
                );
                if ($products) {
                    //register in queue with importer
                    $importer->registerQueue(
                        'Catalog_Default',
                        $products,
                        Dotdigitalgroup_Email_Model_Importer::MODE_BULK,
                        Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID
                    );
                }

                //if to pull store values. will be pulled for each store
            } elseif ($scope == 2) {
                /** @var $store Mage_Core_Model_Store */
                $stores = Mage::app()->getStores();
                foreach ($stores as $store) {
                    $websiteCode = $store->getWebsite()->getCode();
                    $storeCode   = $store->getCode();
                    $products    = $this->_exportCatalog($store->getId());
                    if ($products) {
                        $importer = Mage::getModel('ddg_automation/importer');
                        //register in queue with importer
                        $importer->registerQueue(
                            'Catalog_' . $websiteCode . '_' . $storeCode,
                            $products,
                            Dotdigitalgroup_Email_Model_Importer::MODE_BULK,
                            $store->getWebsiteId()
                        );
                    }
                }
            }
        }

        if (!empty($this->productsToProcess)) {
            $this->getResource()->setProcessed(array_unique($this->productsToProcess));
        }

        if(!empty($this->productIds)) {
            $this->getResource()->setImported(array_unique($this->productIds));
        }
            //@codingStandardsIgnoreStart
        $message = '----------- Catalog sync ----------- : ' . gmdate("H:i:s", microtime(true) - $this->start) .
            ', Total processed = ' . count(array_unique($this->productsToProcess)) . ', Total synced = '. count(array_unique($this->productIds));
            //@codingStandardsIgnoreEnd
        $helper->log($message);
        $response['message'] = $message;


        return $response;
    }

    /**
     * Export catalog.
     *
     * @param $storeId
     *
     * @return array|bool
     */
    protected function _exportCatalog($storeId)
    {
        $productCollection = $this->_getProductsToExport($storeId);

        if ($productCollection) {
            $connectorProducts = array();
            //clear the collection before getting the Limit may not be set.
            $productCollection->clear();

            $this->productIds = array_merge(
                $this->productIds,
                $productCollection->getColumnValues('entity_id')
            );

            foreach ($productCollection as $product) {
                $connectorProduct    = Mage::getModel('ddg_automation/connector_product')
                    ->setProduct($product, $storeId);
                $connectorProducts[] = $connectorProduct;
            }

            return $connectorProducts;
        }

        return false;
    }

    /**
     * Get product collection.
     *
     * @param $store
     *
     * @return bool|Mage_Catalog_Model_Resource_Product_Collection
     */
    protected function _getProductsToExport($store)
    {
        $limit               = Mage::getStoreConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_TRANSACTIONAL_DATA_SYNC_LIMIT
        );
        /** @var Dotdigitalgroup_Email_Model_Resource_Catalog_Collection $connectorCollection */
        $connectorCollection = $this->getCollection();

        $connectorCollection->addFieldToFilter(
            'processed',array(
                'eq' => 0
            )
        );
        $connectorCollection->getSelect()->limit($limit);
        $this->importedProducts = array();
        if ($connectorCollection->getSize()) {
            $productIds       = $connectorCollection->getColumnValues('product_id');
            //Sets this products to be processed processed
            $this->productsToProcess = array_merge(
                $this->productsToProcess,
                $productIds
            );

            /** @var Mage_Catalog_Model_Resource_Product_Collection $productCollection */
            $productCollection = Mage::getResourceModel('catalog/product_collection');
            $productCollection->addAttributeToSelect('*')
                ->addStoreFilter($store)
                ->addAttributeToFilter('entity_id', array('in' => $productIds))
                ->addCategoryIds()
                ->addOptionsToResult()
                ->addUrlRewrite()
                ->setStoreId($store);

            //visibility filter
            if ($visibility = Mage::getStoreConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_CATALOG_VISIBILITY
            )
            ) {
                $visibility = explode(',', $visibility);
                //filter the default option
                $visibility = array_filter($visibility);
                $productCollection->addAttributeToFilter('visibility', array('in' => $visibility));
            }

            //type filter
            if ($type = Mage::getStoreConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_CATALOG_TYPE
            )
            ) {
                $type = explode(',', $type);
                $productCollection->addAttributeToFilter('type_id', array('in' => $type));
            }

            $productCollection->addWebsiteNamesToResult();

            //limit the collection
            $productCollection->getSelect()->limit($limit);

            return $productCollection;
        }

        return false;
    }

    /**
     * Product save after event processor.
     *
     * @param Varien_Event_Observer $observer
     */
    public function handleProductSaveAfter(Varien_Event_Observer $observer)
    {
        try {
            $object    = $observer->getEvent()->getDataObject();
            $productId = $object->getId();
            if ($item = $this->_loadProduct($productId)) {
                if ($item->getProcessed()) {
                    $item->setProcessed(0)->save();
                }
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * Product delete after event processor.
     *
     * @param Varien_Event_Observer $observer
     */
    public function handleProductDeleteAfter(Varien_Event_Observer $observer)
    {
        try {
            /** @var $object Mage_Catalog_Model_Product */
            $object    = $observer->getEvent()->getDataObject();
            $productId = $object->getId();
            if ($item = $this->_loadProduct($productId)) {
                //if imported delete from account
                if ($item->getProcessed()) {
                    $this->_deleteFromAccount($productId);
                }

                //delete from table
                $item->delete();
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * Delete piece of transactional data by key.
     *
     * @param $key
     */
    protected function _deleteFromAccount($key)
    {
        $enabled = Mage::getStoreConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_API_ENABLED
        );
        $sync    = Mage::getStoreConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_CATALOG_ENABLED
        );
        if ($enabled && $sync) {
            $scope = Mage::getStoreConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_CATALOG_VALUES
            );
            if ($scope == 1) {
                //register in queue with importer
                Mage::getModel('ddg_automation/importer')->registerQueue(
                    'Catalog_Default',
                    array($key),
                    Dotdigitalgroup_Email_Model_Importer::MODE_SINGLE_DELETE,
                    Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID
                );
            }

            if ($scope == 2) {
                $stores = Mage::app()->getStores();
                /** @var $store Mage_Core_Model_Store */
                foreach ($stores as $store) {
                    $websiteCode = $store->getWebsite()->getCode();
                    $storeCode   = $store->getCode();

                    //register in queue with importer
                    Mage::getModel('ddg_automation/importer')->registerQueue(
                        'Catalog_' . $websiteCode . '_' . $storeCode,
                        array($key),
                        Dotdigitalgroup_Email_Model_Importer::MODE_SINGLE_DELETE,
                        $store->getWebsiteId()
                    );
                }
            }
        }
    }

    /**
     * Load product, return item otherwise create item.
     *
     * @param $productId
     *
     * @return bool|Varien_Object
     */
    protected function _loadProduct($productId)
    {
        $collection = $this->getCollection()
            ->addFieldToFilter('product_id', $productId)
            ->setPageSize(1);

        if ($collection->getSize()) {
            //@codingStandardsIgnoreStart
            return $collection->getFirstItem();
            //@codingStandardsIgnoreEnd
        } else {
            $this->setProductId($productId)->save();
        }

        return false;
    }

    /**
     * Core config data save before event.
     *
     * @param Varien_Event_Observer $observer
     *
     * @return $this
     */
    public function handleConfigSaveBefore(Varien_Event_Observer $observer)
    {
        if (! Mage::registry('core_config_data_save_before')) {
            if ($groups = $observer->getEvent()->getConfigData()->getGroups()) {
                if (isset($groups['catalog_sync']['fields']['catalog_values']['value'])) {
                    $value
                        = $groups['catalog_sync']['fields']['catalog_values']['value'];
                    Mage::register('core_config_data_save_before', $value);
                }
            }
        }

        if (! Mage::registry('core_config_data_save_before_status')) {
            if ($groups = $observer->getEvent()->getConfigData()->getGroups()) {
                if (isset($groups['data_fields']['fields']['order_statuses']['value'])) {
                    $value
                        = $groups['data_fields']['fields']['order_statuses']['value'];
                    Mage::register(
                        'core_config_data_save_before_status', $value
                    );
                }
            }
        }

        return $this;
    }

    /**
     * Core config data save after event.
     *
     * @param Varien_Event_Observer $observer
     *
     * @return $this
     */
    public function handleConfigSaveAfter(Varien_Event_Observer $observer)
    {
        try {
            if (! Mage::registry('core_config_data_save_after_done')) {
                if ($groups = $observer->getEvent()->getConfigData()->getGroups(
                )
                ) {
                    if (isset($groups['catalog_sync']['fields']['catalog_values']['value'])) {
                        $configAfter
                                      = $groups['catalog_sync']['fields']['catalog_values']['value'];
                        $configBefore = Mage::registry(
                            'core_config_data_save_before'
                        );
                        if ($configAfter != $configBefore) {
                            //@codingStandardsIgnoreStart
                            //reset catalog to re-import
                            $this->getResource()->reset();
                            //@codingStandardsIgnoreEnd
                        }

                        Mage::register(
                            'core_config_data_save_after_done', true
                        );
                    }
                }
            }

            if (! Mage::registry('core_config_data_save_after_done_status')) {
                if ($groups = $observer->getEvent()->getConfigData()->getGroups(
                )
                ) {
                    if (isset($groups['data_fields']['fields']['order_statuses']['value'])) {
                        $configAfter
                                      = $groups['data_fields']['fields']['order_statuses']['value'];
                        $configBefore = Mage::registry(
                            'core_config_data_save_before_status'
                        );
                        if ($configAfter != $configBefore) {
                            //reset all contacts
                            Mage::getResourceModel('ddg_automation/contact')
                                ->resetAllContacts();
                        }

                        Mage::register(
                            'core_config_data_save_after_done_status', true
                        );
                    }
                }
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }

        return $this;
    }

    /**
     * Remove orphaned records from email_catalog table.
     * These are rows in email_catalog for which there is no longer a matching product in catalog_product_entity.
     */
    protected function removeOrphanedProducts()
    {
        $coreResource = Mage::getSingleton('core/resource');
        $write        = $coreResource->getConnection('core_write');
        $catalogTable = $coreResource->getTableName(
            'ddg_automation/catalog'
        );

        $batchSize = 500;
        $startPoint = 0;
        $endPoint = $startPoint + $batchSize;

        do {
            $select = $write->select();
            $batching = $select->reset()
                ->from(
                    array('c' => $catalogTable),
                    array('c.id', 'c.product_id')
                )
                ->joinLeft(
                    array('e' => $coreResource->getTableName(
                        'catalog/product'
                    )),
                    "c.product_id = e.entity_id"
                )
                ->where('c.id >= ?', $startPoint)
                ->where('c.id < ?', $endPoint);

            $rowCount = $write->query($batching)->rowCount();

            $select = $batching->where('e.entity_id is NULL');

            $deleteSql = $select->deleteFromSelect('c');
            $write->query($deleteSql);
            
            $startPoint += $batchSize;
            $endPoint += $batchSize;

        } while ($rowCount > 0);
    }
}
