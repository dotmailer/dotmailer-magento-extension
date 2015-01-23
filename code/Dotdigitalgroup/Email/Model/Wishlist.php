<?php

class Dotdigitalgroup_Email_Model_Wishlist extends Mage_Core_Model_Abstract
{
    private $_start;
    private $_wishlists;
    private $_count = 0;

    /**
     * constructor
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('email_connector/wishlist');
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
        $helper = Mage::helper('connector');
        //resource allocation
        $helper->allowResourceFullExecution();

        foreach (Mage::app()->getWebsites(true) as $website) {
            $enabled = Mage::helper('connector')->getWebsiteConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_WISHLIST_ENABLED, $website);
            if ($enabled) {
                //using bulk api
                $helper->log('---------- Start wishlist bulk sync ----------');
                $this->_start = microtime(true);
                $this->_exportWishlistForWebsite($website);
                //send wishlist as transactional data
                if (isset($this->_wishlists[$website->getId()])) {
                    $client = Mage::helper('connector')->getWebsiteApiClient($website);
                    $websiteWishlists = $this->_wishlists[$website->getId()];
                    //import wishlists in bulk
                    $client->postContactsTransactionalDataImport($websiteWishlists, 'Wishlist');
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
        $limit = Mage::helper('connector')->getWebsiteConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_TRANSACTIONAL_DATA_SYNC_LIMIT, $website);
        $collection = $this->_getWishlistToImport($website, $limit);
        foreach($collection as $emailWishlist){
            $customer = Mage::getModel('customer/customer')->load($emailWishlist->getCustomerId());
            $wishlist = Mage::getModel('wishlist/wishlist')->load($emailWishlist->getWishlistId());
            /** @var  $connectorWishlist */
            $connectorWishlist = Mage::getModel('email_connector/customer_wishlist', $customer);
            $connectorWishlist->setId($wishlist->getId())
                ->setUpdatedAt($wishlist->getUpdatedAt());
            $wishListItemCollection = $wishlist->getItemCollection();
            if (count($wishListItemCollection)) {
                foreach ($wishListItemCollection as $item) {
                    /* @var $product Mage_Catalog_Model_Product */
                    $product = $item->getProduct();
                    $wishlistItem = Mage::getModel('email_connector/customer_wishlist_item', $product)
                        ->setQty($item->getQty())
                        ->setPrice($product);
                    //store for wishlists
                    $connectorWishlist->setItem($wishlistItem);
                    $this->_count++;
                }
                //set wishlists for later use
                $this->_wishlists[$website->getId()][] = $connectorWishlist;
                $emailWishlist->setWishlistImported(1)->save();
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
        return $collection->load();
    }

    private function _exportWishlistForWebsiteInSingle(Mage_Core_Model_Website $website)
    {
        $helper = Mage::helper('connector');
        $client = $helper->getWebsiteApiClient($website);
        $limit = $helper->getWebsiteConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_TRANSACTIONAL_DATA_SYNC_LIMIT, $website);
        $collection = $this->_getModifiedWishlistToImport($website, $limit);
        foreach($collection as $emailWishlist){
            $customer = Mage::getModel('customer/customer')->load($emailWishlist->getCustomerId());
            $wishlist = Mage::getModel('wishlist/wishlist')->load($emailWishlist->getWishlistId());
            /** @var  $connectorWishlist */
            $connectorWishlist = Mage::getModel('email_connector/customer_wishlist', $customer);
            $connectorWishlist->setId($wishlist->getId());
            $wishListItemCollection = $wishlist->getItemCollection();
            if (count($wishListItemCollection)) {
                foreach ($wishListItemCollection as $item) {
                    /* @var $product Mage_Catalog_Model_Product */
                    $product = $item->getProduct();
                    $wishlistItem = Mage::getModel('email_connector/customer_wishlist_item', $product)
                        ->setQty($item->getQty())
                        ->setPrice($product);
                    //store for wishlists
                    $connectorWishlist->setItem($wishlistItem);
                    $this->_count++;
                }
                //send wishlist as transactional data
                $helper->log('---------- Start wishlist single sync ----------');
                $this->_start = microtime(true);
                //import single piece of transactional data
                $result = $client->postContactsTransactionalData($connectorWishlist, 'Wishlist');
                if (!isset($result->message)){
                    $emailWishlist->setWishlistModified(null)->save();
                }
                $message = 'Total time for wishlist single sync : ' . gmdate("H:i:s", microtime(true) - $this->_start);
                $helper->log($message);
            }else{
                $result = $client->deleteContactsTransactionalData($wishlist->getId(), 'Wishlist');
                if (!isset($result->message)){
                    $emailWishlist->setWishlistModified(null)->save();
                }
                $message = 'Total time for wishlist single sync : ' . gmdate("H:i:s", microtime(true) - $this->_start);
                $helper->log($message);
            }
        }
    }

    private function _getModifiedWishlistToImport(Mage_Core_Model_Website $website, $limit = 100)
    {
        $collection = $this->getCollection()
            ->addFieldToFilter('wishlist_modified', 1)
            ->addFieldToFilter('store_id', array('in' => $website->getStoreIds()));

        $collection->getSelect()->limit($limit);
        return $collection->load();
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
            $num = $conn->update($coreResource->getTableName('email_connector/wishlist'),
                array('wishlist_imported' => new Zend_Db_Expr('null')),
                $conn->quoteInto('wishlist_imported is ?', new Zend_Db_Expr('not null'))
            );
        }catch (Exception $e){
            Mage::logException($e);
        }

        return $num;
    }
}