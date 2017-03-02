<?php

class Dotdigitalgroup_Email_Block_Edc extends Mage_Core_Block_Template
{

    /**
     * @var
     */
    public $edcType;

    /**
     * @var array
     */
    protected $_visibility = array(
        Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH,
        Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_CATALOG,
        Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_SEARCH
    );

    /**
     * Constructor.
     */
    protected function _construct()
    {
        parent::_construct();

        if ($this->getRequest()->getControllerName() == 'quoteproducts') {
            $this->edcType = 'quote_products';
        }
    }

    /**
     * Prepare layout, set the template.
     *
     * @return Mage_Core_Block_Abstract|void
     */
    protected function _prepareLayout()
    {
        if ($root = $this->getLayout()->getBlock('root')) {
            $root->setTemplate('page/blank.phtml');
        }
    }

    /**
     * Product related items.
     *
     * @param Mage_Catalog_Model_Product $productModel
     * @param                            $mode
     *
     * @return array
     */
    protected function _getRecommendedProduct(Mage_Catalog_Model_Product $productModel, $mode)
    {
        //array of products to display
        $products = array();
        $productIds = array();
        switch ($mode) {
            case 'related':
                $products = $productModel->getRelatedProducts();
                break;
            case 'upsell':
                $products = $productModel->getUpSellProducts();
                break;
            case 'crosssell':
                $products = $productModel->getCrossSellProducts();
                break;
        }

        foreach ($products as $product) {
            $productIds[] = $product->getId();
        }

        return $productIds;
    }

    /**
     * @param $store
     * @return null|string
     */
    public function getTextForUrl($store)
    {
        $store = Mage::app()->getStore($store);

        return $store->getConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_DYNAMIC_CONTENT_LINK_TEXT
        );
    }

    /**
     * Diplay mode type.
     *
     * @return mixed|string
     */
    public function getMode()
    {
        return Mage::helper('ddg/recommended')->getDisplayType();

    }

    /**
     * Number of the colums.
     *
     * @return int|mixed
     * @throws Exception
     */
    public function getColumnCount()
    {
        return Mage::helper('ddg/recommended')->getDisplayLimitByMode(
            $this->getRequest()->getActionName()
        );
    }

    /**
     * Price html.
     *
     * @param $product
     *
     * @return string
     */
    public function getPriceHtml($product)
    {
        if ($product->getTypeId() == 'bundle') {
            $this->setTemplate('connector/product/bundle_price.phtml');
        } else {
            $this->setTemplate('connector/product/price.phtml');
        }

        $this->setProduct($product);

        return $this->toHtml();
    }

    /**
     * Get collection to EDC type.
     *
     * @return array
     * @throws Exception
     */
    public function getLoadedProductCollection()
    {
        $mode  = $this->getRequest()->getActionName();
        $result = array();
        $limit = Mage::helper('ddg/recommended')->getDisplayLimitByMode($mode);

        if (! $this->edcType) {
            $this->edcType = $mode;
        }

        switch ($this->edcType) {
            case 'recentlyviewed':
                $result = $this->_getRecentlyViewedCollection($limit);
                break;
            case 'push':
                $result = $this->_getProductPushCollection($limit);
                break;
            case 'bestsellers':
                $result =  $this->_getBestSellersCollection($mode, $limit);
                break;
            case 'mostviewed':
                $result = $this->_getMostViewedCollection($mode, $limit);
                break;
            case 'quote_products':
                $result = $this->_getQuoteProductCollection($mode, $limit);
                break;
        }

        return $result;
    }

    /**
     * Get collection for recently viewed products.
     *
     * @param $limit
     *
     * @return array
     * @throws Exception
     */
    protected function _getRecentlyViewedCollection($limit)
    {
        $productsToDisplay = array();
        $customerId        = $this->getRequest()->getParam('customer_id');

        //login customer to receive the recent products
        $session    = Mage::getSingleton('customer/session');
        $isLoggedIn = $session->loginById($customerId);

        /** @var Mage_Reports_Block_Product_Viewed $collection */
        $collection   = Mage::getSingleton('Mage_Reports_Block_Product_Viewed');
        $productItems = $collection->getItemsCollection()
            ->setPageSize($limit);

        Mage::helper('ddg')->log(
            'Recentlyviewed customer  : ' . $customerId . ', limit : ' . $limit
            .
            ', items found : ' . count($productItems)
            . ', is customer logged in : ' . $isLoggedIn . ', products :'
            . count($productsToDisplay)
        );
        //get the product ids from items collection
        $productIds = $productItems->getColumnValues('product_id');
        //get product collection to check for salable
        $productCollection = Mage::getModel('catalog/product')->getCollection()
            ->addPriceData()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('visibility', $this->_visibility)
            ->addFieldToFilter('entity_id', array('in' => $productIds));
        //show products only if is salable
        foreach ($productCollection as $product) {
            if ($product->isSalable()) {
                $productsToDisplay[$product->getId()] = $product;
            }
        }

        $session->logout();

        return $productsToDisplay;
    }

    /**
     * Get collection for push items.
     *
     * @param $limit
     *
     * @return array
     */
    protected function _getProductPushCollection($limit)
    {
        $productsToDisplay = array();
        $productIds        = Mage::helper('ddg/recommended')->getProductPushIds();

        $productCollection = Mage::getResourceModel('catalog/product_collection')
            ->addPriceData()
            ->addAttributeToFilter('entity_id', array('in' => $productIds))
            ->addAttributeToFilter('visibility', $this->_visibility)
            ->addAttributeToSelect(
                array('product_url', 'name', 'store_id', 'small_image', 'price')
            )
            ->setPageSize($limit);

        foreach ($productCollection as $product) {
            //add only salable products
            if ($product->isSaleable()) {
                $productsToDisplay[] = $product;
            }
        }

        return $productsToDisplay;
    }

    /**
     * Get collection for best sellers.
     *
     * @param $mode
     * @param $limit
     *
     * @return Varien_Data_Collection
     */
    protected function _getBestSellersCollection($mode, $limit)
    {
        $from   = Mage::helper('ddg/recommended')->getTimeFromConfig($mode);
        $locale = Mage::app()->getLocale()->getLocale();
        //@codingStandardsIgnoreStart
        $to = Zend_Date::now($locale)->toString(Zend_Date::ISO_8601);
        //@codingStandardsIgnoreEnd

        $productCollection = Mage::getResourceModel('reports/product_collection')
            ->addAttributeToSelect(
                array('product_url', 'name', 'store_id', 'small_image', 'price')
            )
            ->addOrderedQty($from, $to)
            ->setOrder('ordered_qty', 'desc')
            ->addWebsiteFilter(Mage::app()->getWebsite()->getId())
            ->setPageSize($limit);

        Mage::getSingleton('cataloginventory/stock')
            ->addInStockFilterToCollection($productCollection);
        $productCollection->addAttributeToFilter('is_saleable', true)
            ->addAttributeToFilter('visibility', $this->_visibility);

        $catId   = Mage::app()->getRequest()->getParam('category_id', false);
        $catName = Mage::app()->getRequest()->getParam('category_name', false);

        //check for params
        if ($catId or $catName) {
            $category = Mage::getModel('catalog/category');
            //load by category id
            if ($catId) {
                $category->load($catId);
            }

            //load by the category name
            if ($catName) {
                $category->loadByAttribute('name', $catName);
            }

            $productCollection = $this->_joinCategoryOnCollection(
                $productCollection, $category
            );
        }

        return $productCollection;
    }

    /**
     * Get collection for most viewed items.
     *
     * @param $mode
     * @param $limit
     *
     * @return array
     */
    protected function _getMostViewedCollection($mode, $limit)
    {
        $productsToDisplay = array();
        $from              = Mage::helper('ddg/recommended')->getTimeFromConfig(
            $mode
        );
        $locale            = Mage::app()->getLocale()->getLocale();

        //@codingStandardsIgnoreStart
        $to                = Zend_Date::now($locale)->toString(Zend_Date::ISO_8601);
        //@codingStandardsIgnoreEnd
        $productCollection = Mage::getResourceModel('reports/product_collection')
            ->addViewsCount($from, $to)
            ->setPageSize($limit);

        $catId   = Mage::app()->getRequest()->getParam('category_id');
        $catName = Mage::app()->getRequest()->getParam('category_name');
        if ($catId or $catName) {
            $category = Mage::getModel('catalog/category');

            if ($catId) {
                $category->load($catId);
            }

            if ($catName) {
                $category->loadByAttribute('name', $catName);
            }

            $productCollection = $this->_joinCategoryOnCollection(
                $productCollection, $category
            );
        }

        $productIds = $productCollection->getColumnValues('entity_id');
        $productCollection->clear();
        $productCollection = Mage::getResourceModel('catalog/product_collection')
            ->addPriceData()
            ->addIdFilter($productIds)
            ->addAttributeToSelect(
                array('product_url', 'name', 'store_id', 'small_image', 'price')
            )
            ->addAttributeToFilter('visibility', $this->_visibility);

        foreach ($productCollection as $_product) {
            //add only salable products
            if ($_product->isSalable()) {
                $productsToDisplay[] = $_product;
            }
        }

        return $productsToDisplay;
    }

    /**
     * Join categories on product collection.
     *
     * @param $productCollection
     * @param $category
     *
     * @return mixed
     */
    protected function _joinCategoryOnCollection($productCollection, $category)
    {
        //@codingStandardsIgnoreStart
        if ($category->getId()) {
            $productCollection->getSelect()
                ->joinLeft(
                    array("ccpi" => Mage::getSingleton('core/resource')
                        ->getTableName('catalog_category_product_index')),
                    "e.entity_id = ccpi.product_id",
                    array("category_id")
                )
                ->where('ccpi.category_id =?', $category->getId());
        } else {
            Mage::helper('ddg')->log(
                'Most viewed. Category id/name is invalid. It does not exist.'
            );
        }
        //@codingStandardsIgnoreEnd

        return $productCollection;
    }

    /**
     * Get collection for quote products.
     *
     * @param $mode
     * @param $limit
     *
     * @return array
     * @throws Exception
     */
    protected function _getQuoteProductCollection($mode, $limit)
    {
        $quoteModel = Mage::registry('current_quote');

        if (! $quoteModel) {
            Mage::throwException(
                Mage::helper('ddg')->__('no current_quote found for EDC')
            );
        }

        $quoteItems = $quoteModel->getAllVisibleItems();

        $productsToDisplay = $this->getProductsToDisplay(
            $quoteItems, $limit, $mode, 'QUOTE'
        );

        return $productsToDisplay;
    }

    /**
     * Get products to display for order, wishlist and quote EDC.
     *
     * @codingStandardsIgnoreStart
     * @param $items
     * @param $limit
     * @param $mode
     * @param $type
     *
     * @return array
     */
    protected function getProductsToDisplay($items, $limit, $mode, $type)
    {
        //products to be display for recommended pages
        $productsToDisplay        = array();
        $productsToDisplayCounter = 0;

        $numItems = count($items);

        //no product found to display
        if ($numItems == 0 || ! $limit) {
            return array();
        } elseif (count($items) > $limit) {
            $maxPerChild = 1;
        } else {
            $maxPerChild = number_format($limit / count($items));
        }

        Mage::helper('ddg')->log(
            'DYNAMIC ' . $type . ' PRODUCTS : limit ' . $limit . ' products : '
            . $numItems . ', max per child : ' . $maxPerChild
        );

        foreach ($items as $item) {
            $i = 0;
            //parent product
            $product = $item->getProduct();

            //get single product for current mode
            $recommendedProducts = $this->_getRecommendedProduct(
                $product, $mode
            );

            if (! empty($recommendedProducts)) {
                $recommendedProducts = Mage::getModel('catalog/product')
                    ->getCollection()
                    ->addPriceData()
                    ->addIdFilter($recommendedProducts)
                    ->addAttributeToSelect(
                        array('product_url', 'name', 'store_id', 'small_image',
                              'price')
                    );

                foreach ($recommendedProducts as $product) {
                    //check if still exists
                    if ($product->getId() && $productsToDisplayCounter < $limit
                        && $i <= $maxPerChild
                        && $product->isSaleable()
                        && ! $product->getParentId()
                    ) {
                        //we have a product to display
                        $productsToDisplay[$product->getId()] = $product;
                        $i++;
                        $productsToDisplayCounter++;
                    }
                }
            }

            //have reached the limit don't loop for more
            if ($productsToDisplayCounter == $limit) {
                break;
            }
        }

        //check for more space to fill up the table with fallback products
        if ($productsToDisplayCounter < $limit) {
            $fallbackIds       = Mage::helper('ddg/recommended')
                ->getFallbackIds();
            $productCollection = Mage::getModel('catalog/product')
                ->getCollection()
                ->addPriceData()
                ->addIdFilter($fallbackIds)
                ->addAttributeToSelect(
                    array('product_url', 'name', 'store_id', 'small_image',
                          'price')
                );
            foreach ($productCollection as $product) {
                if ($product->isSaleable()) {
                    $productsToDisplay[$product->getId()] = $product;
                }

                //stop the limit was reached
                if (count($productsToDisplay) == $limit) {
                    break;
                }
            }
        }

        //@codingStandardsIgnoreEnd

        return $productsToDisplay;
    }

    /**
     * @param $product Mage_Catalog_Model_Product
     * @return Mage_Catalog_Helper_Image
     */
    public function getProductImage($product)
    {
        $helper = Mage::helper('ddg');
        if ($helper->getWebsiteConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_DYNAMIC_PRODUCT_IMAGE)
            && $product->getTypeId() == "simple"
        ) {
            $parentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($product->getId());
            if (!empty($parentIds)) {
                /** @var Mage_Catalog_Model_Product $parentProduct */
                $parentProduct = Mage::getModel('catalog/product')->load($parentIds[0]);
                return $this->helper('catalog/image')->init($parentProduct, 'small_image')->resize(135);
            }
        }

        return $this->helper('catalog/image')->init($product, 'small_image')->resize(135);
    }
}