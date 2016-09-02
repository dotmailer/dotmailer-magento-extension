<?php

class Dotdigitalgroup_Email_Block_Basket extends Mage_Core_Block_Template
{

    protected $_quote;

    /**
     * Basket itmes.
     *
     * @return mixed
     * @throws Exception
     * @throws Mage_Core_Exception
     */
    public function getBasketItems()
    {
        $params = $this->getRequest()->getParams();

        if ( ! isset($params['quote_id']) || ! isset($params['code'])) {
            Mage::helper('ddg')->log('Basket no quote id or code is set');

            return false;
        }

        $quoteId    = $params['quote_id'];
        $quoteModel = Mage::getModel('sales/quote')->loadByIdWithoutStore($quoteId);

        //check for any quote for this email, don't want to render further
        if ( ! $quoteModel->getId()) {
            Mage::helper('ddg')->log('no quote found for ' . $quoteId);

            return false;
        }
        if ( ! $quoteModel->getIsActive()) {
            Mage::helper('ddg')->log('Cart is not active : ' . $quoteId);

            return false;
        }

        $this->_quote = $quoteModel;

        //Start environment emulation of the specified store
        $storeId      = $quoteModel->getStoreId();
        $appEmulation = Mage::getSingleton('core/app_emulation');
        $appEmulation->startEnvironmentEmulation($storeId);

        $quoteItems = $quoteModel->getAllVisibleItems();

        $itemsData = array();

        foreach ($quoteItems as $quoteItem) {
            $_product    = $quoteItem->getProduct();
            $inStock     = ($_product->getStockItem()->getIsInStock())
                ? 'In Stock'
                : 'Out of stock';
            $total       = Mage::helper('core')->currency(
                $quoteItem->getBaseRowTotalInclTax()
            );
            $productUrl  = $_product->getProductUrl();
            $grandTotal  = Mage::helper('core')->currency(
                $this->getGrandTotal()
            );
            $itemsData[] = array(
                'grandTotal' => $grandTotal,
                'total'      => $total,
                'inStock'    => $inStock,
                'productUrl' => $productUrl,
                'product'    => $_product,
                'qty'        => $quoteItem->getQty()

            );
        }

        return $itemsData;
    }

    /**
     * Grand total.
     *
     * @return mixed
     */
    public function getGrandTotal()
    {
        return $this->_quote->getGrandTotal();

    }

    /**
     * url for "take me to basket" link
     *
     * @return string
     */
    public function getUrlForLink()
    {
        return $this->_quote->getStore()->getUrl(
            'connector/email/getbasket',
            array('quote_id' => $this->_quote->getId())
        );
    }

    /**
     * can show go to basket url
     *
     * @return bool
     */
    public function canShowUrl()
    {
        return (boolean)$this->_quote->getStore()->getWebsite()->getConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CONTENT_LINK_ENABLED
        );
    }

    public function takeMeToCartTextForUrl()
    {
        return $this->_quote->getStore()->getWebsite()->getConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CONTENT_LINK_TEXT
        );
    }


    /**
     * Get dynamic style configuration.
     *
     * @return array
     */
    protected function getDynamicStyle()
    {

        $dynamicStyle = Mage::helper('ddg')->getDynamicStyles();

        return $dynamicStyle;
    }

    public function getProductImage($product)
    {
        $helper = Mage::helper('ddg');
        if ($helper->getWebsiteConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_DYNAMIC_PRODUCT_IMAGE)
            && $product->getTypeId() == "simple"
        ) {
            $parentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($product->getId());
            if (!empty($parentIds)) {
                $parentProduct = Mage::getModel('catalog/product')->load($parentIds[0]);
                return $this->helper('catalog/image')->init($parentProduct, 'small_image')->resize(85);
            }
        }
        return $this->helper('catalog/image')->init($product, 'small_image')->resize(85);
    }

}