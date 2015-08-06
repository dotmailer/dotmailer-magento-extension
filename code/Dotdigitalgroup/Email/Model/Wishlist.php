<?php

class Dotdigitalgroup_Email_Model_Wishlist extends Mage_Core_Model_Abstract
{
    private $_start;
    private $_wishlists;
    private $_count = 0;
    private $_wishlistIds;

    /**
     * constructor
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('ddg_automation/wishlist');
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
        }else {
            $this->setUpdatedAt($now);
        }
        return $this;
    }

    /**
     * @param int $wishListId
     * @return bool|Varien_Object
     */
    public function getWishlist($wishListId)
    {
        $collection = $this->getCollection()
            ->addFieldToFilter('wishlist_id', $wishListId)
            ->setPageSize(1);

        if ($collection->count()) {
            return $collection->getFirstItem();
        }
        return false;
    }

    public function sync()
    {
        $response = array('success' => true, 'message' => '');
        $helper = Mage::helper('ddg');
        //resource allocation
        $helper->allowResourceFullExecution();

        foreach (Mage::app()->getWebsites(true) as $website) {
            $enabled = Mage::helper('ddg')->getWebsiteConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_WISHLIST_ENABLED, $website);
            $apiEnabled = Mage::helper('ddg')->getWebsiteConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_API_ENABLED, $website);
            if ($enabled && $apiEnabled) {
                //using bulk api
                $helper->log('---------- Start wishlist bulk sync ----------');
                $this->_start = microtime(true);
                $this->_exportWishlistForWebsite($website);
                //send wishlist as transactional data
                if (isset($this->_wishlists[$website->getId()])) {
                    $websiteWishlists = $this->_wishlists[$website->getId()];

                    //register in queue with importer
                    $check = Mage::getModel('ddg_automation/importer')->registerQueue(
                        Dotdigitalgroup_Email_Model_Importer::IMPORT_TYPE_WISHLIST,
                        $websiteWishlists,
                        Dotdigitalgroup_Email_Model_Importer::MODE_BULK,
                        $website->getId()
                    );

                    //set imported
                    if ($check) {
                        $this->_setImported($this->_wishlistIds);
                    }
                }
                $message = 'Total time for wishlist bulk sync : ' . gmdate("H:i:s", microtime(true) - $this->_start);
                $helper->log($message);

                //using single api
                $this->_exportWishlistForWebsiteInSingle($website);
            }
        }
        $response['message'] = "wishlist updated: ". $this->_count;
        return $response;
    }

    private function _exportWishlistForWebsite(Mage_Core_Model_Website $website)
    {
        //reset wishlists
        $this->_wishlists = array();
        $this->_wishlistIds = array();
        $limit = Mage::helper('ddg')->getWebsiteConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_TRANSACTIONAL_DATA_SYNC_LIMIT, $website);
        $collection = $this->_getWishlistToImport($website, $limit);
        foreach($collection as $emailWishlist){
            $customer = Mage::getModel('customer/customer')->load($emailWishlist->getCustomerId());
            $wishlist = Mage::getModel('wishlist/wishlist')->load($emailWishlist->getWishlistId());
            /** @var  $connectorWishlist */
            $connectorWishlist = Mage::getModel('ddg_automation/customer_wishlist', $customer);
            $connectorWishlist->setId($wishlist->getId())
                ->setUpdatedAt($wishlist->getUpdatedAt());
            $wishListItemCollection = $wishlist->getItemCollection();
            if ($wishListItemCollection->getSize()) {
                foreach ($wishListItemCollection as $item) {
                    /* @var $product Mage_Catalog_Model_Product */
                    $product = $item->getProduct();
                    $wishlistItem = Mage::getModel('ddg_automation/customer_wishlist_item', $product)
                        ->setQty($item->getQty())
                        ->setPrice($product);
                    //store for wishlists
                    $connectorWishlist->setItem($wishlistItem);
                    $this->_count++;
                }
                //set wishlists for later use
                $this->_wishlists[$website->getId()][] = $connectorWishlist;
                $this->_wishlistIds[] = $emailWishlist->getWishlistId();
            }
        }
    }

    private function _getWishlistToImport(Mage_Core_Model_Website $website, $limit = 100)
    {
        $collection = $this->getCollection()
            ->addFieldToFilter('wishlist_imported', array('null' => true))
            ->addFieldToFilter('store_id', array('in' => $website->getStoreIds()))
            ->addFieldToFilter('item_count', array('gt' => 0));

        $collection->getSelect()->limit($limit);
        return $collection;
    }

    private function _exportWishlistForWebsiteInSingle(Mage_Core_Model_Website $website)
    {
        $helper = Mage::helper('ddg');
        $client = $helper->getWebsiteApiClient($website);
        $limit = $helper->getWebsiteConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_TRANSACTIONAL_DATA_SYNC_LIMIT, $website);
        $collection = $this->_getModifiedWishlistToImport($website, $limit);
        $this->_wishlistIds = array();
        foreach($collection as $emailWishlist){
            $customer = Mage::getModel('customer/customer')->load($emailWishlist->getCustomerId());
            $wishlist = Mage::getModel('wishlist/wishlist')->load($emailWishlist->getWishlistId());
            /** @var  $connectorWishlist */
            $connectorWishlist = Mage::getModel('ddg_automation/customer_wishlist', $customer);
            $connectorWishlist->setId($wishlist->getId());
            $wishListItemCollection = $wishlist->getItemCollection();
            if ($wishListItemCollection->getSize()) {
                foreach ($wishListItemCollection as $item) {
                    /* @var $product Mage_Catalog_Model_Product */
                    $product = $item->getProduct();
                    $wishlistItem = Mage::getModel('ddg_automation/customer_wishlist_item', $product)
                        ->setQty($item->getQty())
                        ->setPrice($product);
                    //store for wishlists
                    $connectorWishlist->setItem($wishlistItem);
                    $this->_count++;
                }
                //send wishlist as transactional data
                $helper->log('---------- Start wishlist single sync ----------');
                $this->_start = microtime(true);
                //register in queue with importer
                $check = Mage::getModel('ddg_automation/importer')->registerQueue(
                    Dotdigitalgroup_Email_Model_Importer::IMPORT_TYPE_WISHLIST,
                    $connectorWishlist,
                    Dotdigitalgroup_Email_Model_Importer::MODE_SINGLE,
                    $website->getId()
                );
                if ($check) {
                    $this->_wishlistIds[] = $emailWishlist->getWishlistId();
                }
                $message = 'Total time for wishlist single sync : ' . gmdate("H:i:s", microtime(true) - $this->_start);
                $helper->log($message);
            }else{
                //register in queue with importer
                $check = Mage::getModel('ddg_automation/importer')->registerQueue(
                    Dotdigitalgroup_Email_Model_Importer::IMPORT_TYPE_WISHLIST,
                    array($wishlist->getId()),
                    Dotdigitalgroup_Email_Model_Importer::MODE_SINGLE_DELETE,
                    $website->getId()
                );
                if ($check) {
                    $this->_wishlistIds[] = $emailWishlist->getWishlistId();
                }
                $message = 'Total time for wishlist single sync : ' . gmdate("H:i:s", microtime(true) - $this->_start);
                $helper->log($message);
            }
        }
        if(!empty($this->_wishlistIds))
            $this->_setImported($this->_wishlistIds, true);
    }

    private function _getModifiedWishlistToImport(Mage_Core_Model_Website $website, $limit = 100)
    {
        $collection = $this->getCollection()
            ->addFieldToFilter('wishlist_modified', 1)
            ->addFieldToFilter('store_id', array('in' => $website->getStoreIds()));

        $collection->getSelect()->limit($limit);
        return $collection;
    }

    /**
     * Reset the email reviews for reimport.
     *
     * @return int
     */
    public function reset()
    {
        /** @var $coreResource Mage_Core_Model_Resource */
        $coreResource = Mage::getSingleton('core/resource');

        /** @var $conn Varien_Db_Adapter_Pdo_Mysql */
        $conn = $coreResource->getConnection('core_write');
        try{
            $num = $conn->update($coreResource->getTableName('ddg_automation/wishlist'),
                array('wishlist_imported' => new Zend_Db_Expr('null'), 'wishlist_modified' => new Zend_Db_Expr('null'))
            );
        }catch (Exception $e){
            Mage::logException($e);
        }

        return $num;
    }

    /**
     * set imported in bulk query
     *
     * @param $ids
     * @param $modified
     */
    private function _setImported($ids, $modified = false)
    {
        try{
            $coreResource = Mage::getSingleton('core/resource');
            $write = $coreResource->getConnection('core_write');
            $tableName = $coreResource->getTableName('email_wishlist');
            $ids = implode(', ', $ids);
            $now = Mage::getSingleton('core/date')->gmtDate();
            if($modified)
                $write->update($tableName, array('wishlist_modified' => new Zend_Db_Expr('null'), 'updated_at' => $now), "wishlist_id IN ($ids)");
            else
                $write->update($tableName, array('wishlist_imported' => 1, 'updated_at' => $now), "wishlist_id IN ($ids)");
        }catch (Exception $e){
            Mage::logException($e);
        }
    }
}