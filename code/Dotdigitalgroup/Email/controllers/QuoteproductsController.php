<?php
require_once 'Dotdigitalgroup' . DS . 'Email' . DS . 'controllers' . DS . 'ResponseController.php';

class Dotdigitalgroup_Email_QuoteproductsController extends Dotdigitalgroup_Email_ResponseController
{
    /**
     * @return Mage_Core_Controller_Front_Action|void
     */
    public function preDispatch()
    {
        //authenticate
        $this->authenticate();
        if ($this->getRequest()->getActionName() != 'push') {
            $quoteId = $this->getRequest()->getParam('quote_id', false);
            //check for quote id param
            if ($quoteId) {
                //check if quote exists
                $quote = Mage::getModel('sales/quote')->load($quoteId);
                if ($quote->getId()) {
                    Mage::register('current_quote', $quote);
                    //start app emulation
                    $storeId = $quote->getStoreId();
                    $appEmulation = Mage::getSingleton('core/app_emulation');
                    $appEmulation->startEnvironmentEmulation($storeId);
                } else {
                    $message = 'Dynamic : Quote not found: ' . $quoteId;
                    Mage::helper('ddg')->log($message);
                    Mage::helper('ddg')->rayLog('100', $message);
                }
            } else {
                Mage::helper('ddg')->log('Dynamic : order_id missing :' . $quoteId);
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
        $products = $this->getLayout()->createBlock('ddg_automation/edc', 'connector_recommended_quote_related', array(
            'template' => 'connector/product/list.phtml' , 'edc_type' => 'quote_products'
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
        $products = $this->getLayout()->createBlock('ddg_automation/edc', 'connector_recommended_quote_crosssell', array(
            'template' => 'connector/product/list.phtml' , 'edc_type' => 'quote_products'
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
        $products = $this->getLayout()->createBlock('ddg_automation/edc', 'connector_recommended_quote_upsell', array(
            'template' => 'connector/product/list.phtml' , 'edc_type' => 'quote_products'
        ));
        //append upsell products
        $this->getLayout()->getBlock('content')->append($products);
        $this->renderLayout();
        $this->checkContentNotEmpty($this->getLayout()->getOutput());
    }
}