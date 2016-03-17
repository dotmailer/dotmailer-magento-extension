<?php

class Dotdigitalgroup_Email_Model_Wishlist extends Mage_Core_Model_Abstract
{

    protected $_start;
    protected $_wishlists;
    protected $_count = 0;
    protected $_wishlistIds;

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
        } else {
            $this->setUpdatedAt($now);
        }

        return $this;
    }

    /**
     * @param int $wishListId
     *
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
        $helper   = Mage::helper('ddg');
        //resource allocation
        $helper->allowResourceFullExecution();

        foreach (Mage::app()->getWebsites(true) as $website) {
            $enabled    = Mage::helper('ddg')->getWebsiteConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_WISHLIST_ENABLED,
                $website
            );
            $apiEnabled = Mage::helper('ddg')->getWebsiteConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_API_ENABLED,
                $website
            );
            $storeIds   = $website->getStoreIds();
            if ($enabled && $apiEnabled && ! empty($storeIds)) {
                //using bulk api
                $helper->log(
                    '---------- Start wishlist bulk sync ----------'
                    . $website->getName()
                );
                $this->_start = microtime(true);
                $this->_exportWishlistForWebsite($website);
                //send wishlist as transactional data
                if (isset($this->_wishlists[$website->getId()])) {
                    $websiteWishlists = $this->_wishlists[$website->getId()];

                    //register in queue with importer
                    $check = Mage::getModel('ddg_automation/importer')
                        ->registerQueue(
                            Dotdigitalgroup_Email_Model_Importer::IMPORT_TYPE_WISHLIST,
                            $websiteWishlists,
                            Dotdigitalgroup_Email_Model_Importer::MODE_BULK,
                            $website->getId()
                        );

                    //set imported
                    if ($check) {
                        $this->getResource()->setImported($this->_wishlistIds);
                    }
                }
                $message = 'Total time for wishlist bulk sync : ' . gmdate(
                    "H:i:s", microtime(true) - $this->_start
                );
                $helper->log($message);

                //using single api
                $this->_exportWishlistForWebsiteInSingle($website);
            }
        }
        $response['message'] = "wishlist updated: " . $this->_count;

        return $response;
    }

    protected function _exportWishlistForWebsite(Mage_Core_Model_Website $website
    ) 
    {
        //reset wishlists
        $this->_wishlists   = array();
        $this->_wishlistIds = array();
        $limit              = Mage::helper('ddg')->getWebsiteConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_TRANSACTIONAL_DATA_SYNC_LIMIT,
            $website
        );
        $emailWishlist      = $this->_getWishlistToImport($website, $limit);
        $this->_wishlistIds = $emailWishlist->getColumnValues('wishlist_id');

        if ( ! empty($this->_wishlistIds)) {
            $collection = Mage::getModel('wishlist/wishlist')
                ->getCollection()
                ->addFieldToFilter(
                    'main_table.wishlist_id', array('in' => $this->_wishlistIds)
                )
                ->addFieldToFilter('customer_id', array('notnull' => 'true'));

            $collection->getSelect()
                ->joinLeft(
                    array('c' => 'customer_entity'),
                    'c.entity_id = customer_id',
                    array('email', 'store_id')
                );

            foreach ($collection as $wishlist) {
                $connectorWishlist = Mage::getModel(
                    'ddg_automation/customer_wishlist'
                );
                $connectorWishlist
                    ->setId($wishlist->getId())
                    ->setUpdatedAt($wishlist->getUpdatedAt())
                    ->setCustomerId($wishlist->getCustomerId())
                    ->setEmail($wishlist->getEmail());
                $wishListItemCollection = $wishlist->getItemCollection();
                if ($wishListItemCollection->getSize()) {
                    foreach ($wishListItemCollection as $item) {
                        /* @var $product Mage_Catalog_Model_Product */
                        $product      = $item->getProduct();
                        $wishlistItem = Mage::getModel(
                            'ddg_automation/customer_wishlist_item', $product
                        )
                            ->setQty($item->getQty())
                            ->setPrice($product);
                        //store for wishlists
                        $connectorWishlist->setItem($wishlistItem);
                        $this->_count++;
                    }
                    //set wishlists for later use
                    $this->_wishlists[$website->getId()][] = $connectorWishlist;
                }
            }
        }
    }

    protected function _getWishlistToImport(Mage_Core_Model_Website $website,
        $limit = 100
    ) 
    {
        $collection = $this->getCollection()
            ->addFieldToFilter('wishlist_imported', array('null' => true))
            ->addFieldToFilter(
                'store_id', array('in' => $website->getStoreIds())
            )
            ->addFieldToFilter('item_count', array('gt' => 0));

        $collection->getSelect()->limit($limit);

        return $collection;
    }

    protected function _exportWishlistForWebsiteInSingle(Mage_Core_Model_Website $website
    ) 
    {
        $helper             = Mage::helper('ddg');
        $limit              = $helper->getWebsiteConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_TRANSACTIONAL_DATA_SYNC_LIMIT,
            $website
        );
        $collection         = $this->_getModifiedWishlistToImport(
            $website, $limit
        );
        $this->_wishlistIds = array();
        //email_wishlist wishlist ids
        $wishlistIds = $collection->getColumnValues('wishlist_id');

        $wishlistCollection = Mage::getModel('wishlist/wishlist')->getCollection()
            ->addFieldToFilter('wishlist_id', array('in' => $wishlistIds));
        $wishlistCollection->getSelect()
            ->joinLeft(
                array('c' => 'customer_entity'),
                'c.entity_id = customer_id',
                array('email', 'store_id')
            );

        foreach ($wishlistCollection as $wishlist) {

            $wishlistId = $wishlist->getid();
            $wishlistItems = $wishlist->getItemCollection();

            $connectorWishlist = Mage::getModel('ddg_automation/customer_wishlist');
            $connectorWishlist->setId($wishlistId)
                ->setUpdatedAt($wishlist->getUpdatedAt())
                ->setCustomerId($wishlist->getCustomerId())
                ->setEmail($wishlist->getEmail());

            if ($wishlistItems->getSize()) {
                foreach ($wishlistItems as $item) {
                    /* @var $product Mage_Catalog_Model_Product */
                    $product      = $item->getProduct();
                    $wishlistItem = Mage::getModel(
                        'ddg_automation/customer_wishlist_item', $product
                    )
                        ->setQty($item->getQty())
                        ->setPrice($product);
                    //store for wishlists
                    $connectorWishlist->setItem($wishlistItem);
                    $this->_count++;
                }
                //send wishlist as transactional data
                $helper->log(
                    '---------- Start wishlist single sync ----------'
                );
                $this->_start = microtime(true);
                //register in queue with importer
                $check = Mage::getModel('ddg_automation/importer')
                    ->registerQueue(
                        Dotdigitalgroup_Email_Model_Importer::IMPORT_TYPE_WISHLIST,
                        $connectorWishlist,
                        Dotdigitalgroup_Email_Model_Importer::MODE_SINGLE,
                        $website->getId()
                    );
                if ($check) {
                    $this->_wishlistIds[] = $wishlistId;
                }
                $message = 'Total time for wishlist single sync : ' . gmdate(
                    "H:i:s", microtime(true) - $this->_start
                );
                $helper->log($message);
            } else {
                //register in queue with importer
                $check = Mage::getModel('ddg_automation/importer')
                    ->registerQueue(
                        Dotdigitalgroup_Email_Model_Importer::IMPORT_TYPE_WISHLIST,
                        array($wishlist->getId()),
                        Dotdigitalgroup_Email_Model_Importer::MODE_SINGLE_DELETE,
                        $website->getId()
                    );
                if ($check) {
                    $this->_wishlistIds[] = $wishlistId;
                }
                $message = 'Total time for wishlist single sync : ' . gmdate(
                    "H:i:s", microtime(true) - $this->_start
                );
                $helper->log($message);
            }
        }
        if ( ! empty($this->_wishlistIds)) {
            $this->getResource()->setImported($this->_wishlistIds, true);
        }
    }

    protected function _getModifiedWishlistToImport(Mage_Core_Model_Website $website,
        $limit = 100
    ) 
    {
        $collection = $this->getCollection()
            ->addFieldToFilter('wishlist_modified', 1)
            ->addFieldToFilter(
                'store_id', array('in' => $website->getStoreIds())
            )
            ->addFieldToSelect('wishlist_id');

        $collection->getSelect()->limit($limit);

        return $collection;
    }
}