<?php
require_once 'Dotdigitalgroup' . DS . 'Email' . DS . 'controllers' . DS . 'ResponseController.php';

class Dotdigitalgroup_Email_QuoteproductsController extends Dotdigitalgroup_Email_ResponseController
{
    /**
     * @return Mage_Core_Controller_Front_Action|void
     */
    public function preDispatch()
    {
        Mage::helper('connector')->auth($this->getRequest()->getParam('code'));
        if ($this->getRequest()->getActionName() != 'push') {
            $quoteId = $this->getRequest()->getParam('quote_id', false);
            //check for quote id param
            if ($quoteId) {
                //check if quote exists
                $quote = Mage::getModel('sales/quote')->load($quoteId);
                if ($quote->getId()) {
                    //start app emulation
                    $storeId = $quote->getStoreId();
                    $appEmulation = Mage::getSingleton('core/app_emulation');
                    $appEmulation->startEnvironmentEmulation($storeId);
                } else {
                    $message = 'Dynamic : Quote not found: ' . $quoteId;
                    Mage::helper('connector')->log($message);
                    Mage::helper('connector')->rayLog('100', $message);
                }
            } else {
                Mage::helper('connector')->log('Dynamic : order_id missing :' . $quoteId);
            }
        }

        parent::preDispatch();
    }

    /**
     * Related products.
     */
    public function relatedAction()
    {
        $this->loadLayout();
        $products = $this->getLayout()->createBlock('email_connector/recommended_quoteproducts', 'connector_recommended_quote_related', array(
            'template' => 'connector/product/list.phtml'
        ));
        //append related products
        $this->getLayout()->getBlock('content')->append($products);
        $this->renderLayout();
        $this->checkContentNotEmpty($this->getLayout()->getOutput());
    }

    /**
     * Crosssell products.
     */
    public function crosssellAction()
    {
        $this->loadLayout();
        $products = $this->getLayout()->createBlock('email_connector/recommended_quoteproducts', 'connector_recommended_quote_crosssell', array(
            'template' => 'connector/product/list.phtml'
        ));
        //append crosssell products.
        $this->getLayout()->getBlock('content')->append($products);
        $this->renderLayout();
        $this->checkContentNotEmpty($this->getLayout()->getOutput());
    }

    /**
     * Upsell products.
     */
    public function upsellAction()
    {
        $this->loadLayout();
        $products = $this->getLayout()->createBlock('email_connector/recommended_quoteproducts', 'connector_recommended_quote_upsell', array(
            'template' => 'connector/product/list.phtml'
        ));
        //append upsell products
        $this->getLayout()->getBlock('content')->append($products);
        $this->renderLayout();
        $this->checkContentNotEmpty($this->getLayout()->getOutput());
    }

    /**
     * Products that are set to manually push as related.
     */
    public function pushAction()
    {
        $this->loadLayout();
        $products = $this->getLayout()->createBlock('email_connector/recommended_push', 'connector_product_push', array(
            'template' => 'connector/product/list.phtml'
        ));
        //append push products
        $this->getLayout()->getBlock('content')->append($products);
        $this->renderLayout();
        $this->checkContentNotEmpty($this->getLayout()->getOutput());
    }
}