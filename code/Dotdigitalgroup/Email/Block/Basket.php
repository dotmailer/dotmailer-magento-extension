<?php

use Dotdigitalgroup_Email_Block_ProductImageTrait as ProductImageTrait;

class Dotdigitalgroup_Email_Block_Basket extends Mage_Core_Block_Template
{
    use ProductImageTrait;

    /**
     * @var Mage_Sales_Model_Quote
     */
    public $quoteModel;

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

        if (!isset($params['quote_id']) || !isset($params['code'])) {
            Mage::helper('ddg')->log('Basket no quote id or code is set');

            return false;
        }

        $quoteId    = $params['quote_id'];
        $quoteModel = Mage::getModel('sales/quote')->loadByIdWithoutStore($quoteId);

        //check for any quote for this email, don't want to render further
        if (!$quoteModel->getId()) {
            Mage::helper('ddg')->log('no quote found for ' . $quoteId);

            return false;
        }

        if (!$quoteModel->getIsActive()) {
            Mage::helper('ddg')->log('Cart is not active : ' . $quoteId);

            return false;
        }

        $this->quoteModel = $quoteModel;

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
        return $this->quoteModel->getGrandTotal();

    }

    /**
     * url for "take me to basket" link
     *
     * @return string
     */
    public function getUrlForLink()
    {
        return $this->quoteModel->getStore()->getUrl(
            'connector/email/getbasket',
            array('quote_id' => $this->quoteModel->getId())
        );
    }

    /**
     * Can show go to basket url.
     *
     * @return bool
     */
    public function canShowUrl()
    {
        return (boolean)$this->quoteModel->getStore()->getWebsite()->getConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CONTENT_LINK_ENABLED
        );
    }

    /**
     * @return mixed
     */
    public function takeMeToCartTextForUrl()
    {
        return $this->quoteModel->getStore()->getWebsite()->getConfig(
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

    /**
     * @param $product
     * @param string $code
     * @return mixed
     */
    public function getProductLabel($product, $code = 'small_image')
    {
        $label = $product->getData( $code . '_label');

        if (empty($label)) {
            $label = $product->getName();
        }

        return $label;
    }

}