<?php

class Dotdigitalgroup_Email_Model_Wishlist extends Mage_Core_Model_Abstract
{

    /**
     * @var mixed
     */
    public $start;
    /**
     *
     * @var array
     */
    public $wishlists;
    /**
     * @var int
     */
    public $countWishlists = 0;
    /**
     * @var array
     */
    public $wishlistIds;

    /**
     * Constructor.
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('ddg_automation/wishlist');
    }

    /**
     * @return $this
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

        if ($collection->getSize()) {
            //@codingStandardsIgnoreStart
            return $collection->getFirstItem();
            //@codingStandardsIgnoreEnd
        }

        return false;
    }

    /**
     * Sync wishlists.
     *
     * @return array
     */
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
                $this->start = microtime(true);
                $this->_exportWishlistForWebsite($website);
                //send wishlist as transactional data
                if (isset($this->wishlists[$website->getId()])) {
                    $websiteWishlists = $this->wishlists[$website->getId()];

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
                        $this->getResource()->setImported($this->wishlistIds);
                    }
                }

                //@codingStandardsIgnoreStart
                $message = 'Total time for wishlist bulk sync : ' . gmdate("H:i:s", microtime(true) - $this->start);
                //@codingStandardsIgnoreEnd
                $helper->log($message);

                //using single api
                $this->_exportWishlistForWebsiteInSingle($website);
            }
        }

        $response['message'] = "Wishlists updated: " . $this->countWishlists;

        return $response;
    }

    /**
     * Sync single wishlist.
     *
     * @param Mage_Core_Model_Website $website
     */
    protected function _exportWishlistForWebsite(Mage_Core_Model_Website $website)
    {
        //reset wishlists
        $this->wishlists = array();
        $this->wishlistIds = array();
        $limit              = Mage::helper('ddg')->getWebsiteConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_TRANSACTIONAL_DATA_SYNC_LIMIT,
            $website
        );
        $emailWishlist      = $this->_getWishlistToImport($website, $limit);
        $this->wishlistIds = $emailWishlist->getColumnValues('wishlist_id');

        if (!empty($this->wishlistIds)) {
            $collection = Mage::getModel('wishlist/wishlist')
                ->getCollection()
                ->addFieldToFilter(
                    'main_table.wishlist_id', array('in' => $this->wishlistIds)
                )
                ->addFieldToFilter('customer_id', array('notnull' => 'true'));

            //@codingStandardsIgnoreStart
            $collection->getSelect()
                ->joinLeft(
                    array('c' => Mage::getSingleton('core/resource')
                        ->getTableName('customer/entity')),
                    'c.entity_id = customer_id',
                    array('email', 'store_id')
                );
            //@codingStandardsIgnoreEnd
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
                        $product      = $item->getProduct();
                        $wishlistItem = Mage::getModel(
                            'ddg_automation/customer_wishlist_item', $product
                        )
                            ->setQty($item->getQty())
                            ->setPrice($product);
                        //store for wishlists
                        $connectorWishlist->setItem($wishlistItem);
                        $this->countWishlists++;
                    }

                    //set wishlists for later use
                    $this->wishlists[$website->getId()][] = $connectorWishlist;
                }
            }
        }
    }

    /**
     * Get wishlists pending for sync.
     *
     * @param Mage_Core_Model_Website $website
     * @param int                     $limit
     *
     * @return Mage_Eav_Model_Entity_Collection_Abstract
     */
    protected function _getWishlistToImport(Mage_Core_Model_Website $website, $limit = 100)
    {
        $collection = $this->getCollection()
            ->addFieldToFilter('wishlist_imported', array('null' => true))
            ->addFieldToFilter(
                'store_id', array('in' => $website->getStoreIds())
            )
            ->addFieldToFilter('item_count', array('gt' => 0));

        //@codingStandardsIgnoreStart
        $collection->getSelect()->limit($limit);
        //@codingStandardsIgnoreEnd

        return $collection;
    }

    /**
     * Get wishlists pending for sync.
     *
     * @param Mage_Core_Model_Website $website
     */
    protected function _exportWishlistForWebsiteInSingle(Mage_Core_Model_Website $website)
    {
        $helper             = Mage::helper('ddg');
        $limit              = $helper->getWebsiteConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_TRANSACTIONAL_DATA_SYNC_LIMIT,
            $website
        );
        $collection         = $this->_getModifiedWishlistToImport(
            $website, $limit
        );
        $this->wishlistIds = array();
        //email_wishlist wishlist ids
        $wishlistIds = $collection->getColumnValues('wishlist_id');

        $wishlistCollection = Mage::getModel('wishlist/wishlist')->getCollection()
            ->addFieldToFilter('wishlist_id', array('in' => $wishlistIds));
        //@codingStandardsIgnoreStart
        $wishlistCollection->getSelect()
            ->joinLeft(
                array('c' => Mage::getSingleton('core/resource')
                    ->getTableName('customer/entity')),
                'c.entity_id = customer_id',
                array('email', 'store_id')
            );
        //@codingStandardsIgnoreEnd

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
                    $product      = $item->getProduct();
                    $wishlistItem = Mage::getModel(
                        'ddg_automation/customer_wishlist_item', $product
                    )
                        ->setQty($item->getQty())
                        ->setPrice($product);
                    //store for wishlists
                    $connectorWishlist->setItem($wishlistItem);
                    $this->countWishlists++;
                }

                //send wishlist as transactional data
                $this->start = microtime(true);
                //register in queue with importer
                $check = Mage::getModel('ddg_automation/importer')
                    ->registerQueue(
                        Dotdigitalgroup_Email_Model_Importer::IMPORT_TYPE_WISHLIST,
                        $connectorWishlist,
                        Dotdigitalgroup_Email_Model_Importer::MODE_SINGLE,
                        $website->getId()
                    );
                if ($check) {
                    $this->wishlistIds[] = $wishlistId;
                }
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
                    $this->wishlistIds[] = $wishlistId;
                }
            }
        }

        if (!empty($this->wishlistIds)) {
            $this->getResource()->setImported($this->wishlistIds, true);
        }
    }

    /**
     * Get wishlists marked as modified.
     *
     * @param Mage_Core_Model_Website $website
     * @param int                     $limit
     *
     * @return mixed
     */
    protected function _getModifiedWishlistToImport(Mage_Core_Model_Website $website, $limit = 100)
    {
        $collection = $this->getCollection()
            ->addFieldToFilter('wishlist_modified', 1)
            ->addFieldToFilter(
                'store_id', array('in' => $website->getStoreIds())
            )
            ->addFieldToSelect('wishlist_id');

        //@codingStandardsIgnoreStart
        $collection->getSelect()->limit($limit);
        //@codingStandardsIgnoreEnd

        return $collection;
    }
}