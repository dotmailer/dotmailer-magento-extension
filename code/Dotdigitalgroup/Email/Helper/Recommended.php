<?php

class Dotdigitalgroup_Email_Helper_Recommended extends Mage_Core_Helper_Abstract
{

    const XML_PATH_RELATED_PRODUCTS_TYPE = 'connector_dynamic_content/products/related_display_type';
    const XML_PATH_UPSELL_PRODUCTS_TYPE = 'connector_dynamic_content/products/upsell_display_type';
    const XML_PATH_CROSSSELL_PRODUCTS_TYPE = 'connector_dynamic_content/products/crosssell_display_type';
    const XML_PATH_BESTSELLER_PRODUCT_TYPE = 'connector_dynamic_content/products/bestsellers_display_type';
    const XML_PATH_MOSTVIEWED_PRODUCT_TYPE = 'connector_dynamic_content/products/most_viewed_display_type';
    const XML_PATH_RECENTLYVIEWED_PRODUCT_TYPE = 'connector_dynamic_content/products/recently_viewed_display_type';
    const XML_PATH_PRODUCTPUSH_TYPE = 'connector_dynamic_content/manual_product_search/display_type';


    const XML_PATH_RELATED_PRODUCTS_ITEMS = 'connector_dynamic_content/products/related_items_to_display';
    const XML_PATH_UPSELL_PRODUCTS_ITEMS = 'connector_dynamic_content/products/upsell_items_to_display';
    const XML_PATH_CROSSSELL_PRODUCTS_ITEMS = 'connector_dynamic_content/products/crosssell_items_to_display';
    const XML_PATH_BESTSELLER_PRODUCT_ITEMS = 'connector_dynamic_content/products/bestsellers_items_to_display';
    const XML_PATH_MOSTVIEWED_PRODUCT_ITEMS = 'connector_dynamic_content/products/most_viewed_items_to_display';
    const XML_PATH_RECENTLYVIEWED_PRODUCT_ITEMS = 'connector_dynamic_content/products/recently_viewed_items_to_display';

    const XML_PATH_PRODUCTPUSH_DISPLAY_ITEMS = 'connector_dynamic_content/manual_product_search/items_to_display';
    const XML_PATH_BESTSELLER_TIME_PERIOD = 'connector_dynamic_content/products/bestsellers_time_period';
    const XML_PATH_MOSTVIEWED_TIME_PERIOD = 'connector_dynamic_content/products/most_viewed_time_period';
    const XML_PATH_PRODUCTPUSH_ITEMS = 'connector_dynamic_content/manual_product_search/products_push_items';
    const XML_PATH_FALLBACK_PRODUCTS_ITEMS = 'connector_dynamic_content/fallback_products/product_list';

    public $periods = array('week', 'month', 'year');

    /**
     * Dispay type
     *
     * @return mixed|string grid:list
     */
    public function getDisplayType()
    {
        $mode = Mage::app()->getRequest()->getActionName();
        $type = '';

        switch ($mode) {
            case 'related':
                $type = $this->getRelatedProductsType();
                break;
            case 'upsell':
                $type = $this->getUpsellProductsType();
                break;
            case 'crosssell':
                $type = $this->getCrosssellProductsType();
                break;
            case 'bestsellers':
                $type = $this->getBestSellerProductsType();
                break;
            case 'mostviewed':
                $type = $this->getMostViewedProductsType();
                break;
            case 'recentlyviewed':
                $type = $this->getRecentlyviewedProductsType();
                break;
            case 'push':
                $type = $this->getProductpushProductsType();
        }

        return $type;
    }

    public function getRelatedProductsType()
    {
        return Mage::getStoreConfig(self::XML_PATH_RELATED_PRODUCTS_TYPE);
    }

    public function getUpsellProductsType()
    {
        return Mage::getStoreConfig(self::XML_PATH_UPSELL_PRODUCTS_TYPE);

    }

    public function getCrosssellProductsType()
    {
        return Mage::getStoreConfig(self::XML_PATH_CROSSSELL_PRODUCTS_TYPE);
    }

    public function getBestSellerProductsType()
    {
        return Mage::getStoreConfig(self::XML_PATH_BESTSELLER_PRODUCT_TYPE);
    }

    public function getMostViewedProductsType()
    {
        return Mage::getStoreConfig(self::XML_PATH_MOSTVIEWED_PRODUCT_TYPE);
    }

    public function getRecentlyviewedProductsType()
    {
        return Mage::getStoreConfig(self::XML_PATH_RECENTLYVIEWED_PRODUCT_TYPE);
    }

    public function getProductpushProductsType()
    {
        return Mage::getStoreConfig(self::XML_PATH_PRODUCTPUSH_TYPE);
    }


    /**
     * Limit of products displayed.
     *
     * @param $mode
     *
     * @return int|mixed
     */
    public function getDisplayLimitByMode($mode)
    {
        $result = 0;

        switch ($mode) {
            case 'related':
                $result = Mage::getStoreConfig(
                    self::XML_PATH_RELATED_PRODUCTS_ITEMS
                );
                break;
            case 'upsell':
                $result = Mage::getStoreConfig(
                    self::XML_PATH_UPSELL_PRODUCTS_ITEMS
                );
                break;
            case 'crosssell':
                $result = Mage::getStoreConfig(
                    self::XML_PATH_CROSSSELL_PRODUCTS_ITEMS
                );
                break;
            case 'bestsellers':
                $result = Mage::getStoreConfig(
                    self::XML_PATH_BESTSELLER_PRODUCT_ITEMS
                );
                break;
            case 'mostviewed':
                $result = Mage::getStoreConfig(
                    self::XML_PATH_MOSTVIEWED_PRODUCT_ITEMS
                );
                break;
            case 'recentlyviewed':
                $result = Mage::getStoreConfig(
                    self::XML_PATH_RECENTLYVIEWED_PRODUCT_ITEMS
                );
                break;
            case 'push':
                $result = Mage::getStoreConfig(
                    self::XML_PATH_PRODUCTPUSH_DISPLAY_ITEMS
                );
        }

        return $result;
    }

    public function getFallbackIds()
    {
        $fallbackIds = Mage::getStoreConfig(
            self::XML_PATH_FALLBACK_PRODUCTS_ITEMS
        );
        if ($fallbackIds) {
            return explode(
                ',',
                Mage::getStoreConfig(self::XML_PATH_FALLBACK_PRODUCTS_ITEMS)
            );
        }

        return array();
    }

    public function getTimeFromConfig($config)
    {
        $now = new Zend_Date();
        $period = '';
        if ($config == 'mostviewed') {
            $period = Mage::getStoreConfig(
                self::XML_PATH_MOSTVIEWED_TIME_PERIOD
            );
        } elseif ($config == 'bestsellers') {
            $period = Mage::getStoreConfig(
                self::XML_PATH_BESTSELLER_TIME_PERIOD
            );
        } elseif ($config == 'recentlyviewed') {
            $period = Mage::getStoreConfig(
                self::XML_PATH_MOSTVIEWED_TIME_PERIOD
            );
        }

        if ($period == 'week') {
            $sub = Zend_Date::WEEK;
        } elseif ($period == 'month' || $period == 'M') {
            $sub = Zend_Date::MONTH;
        } elseif ($period == 'year') {
            $sub = Zend_Date::YEAR;
        }

        if (isset($sub)) {
            $period = $now->sub(1, $sub);

            return $period->tostring(Zend_Date::ISO_8601);
        }
    }

    public function getProductPushIds()
    {
        $productIds = Mage::getStoreConfig(self::XML_PATH_PRODUCTPUSH_ITEMS);

        return explode(',', $productIds);
    }

}