<?php
require_once 'Dotdigitalgroup' . DS . 'Email' . DS . 'controllers' . DS . 'ResponseController.php';

class Dotdigitalgroup_Email_ProductsController extends Dotdigitalgroup_Email_ResponseController
{
	/**
	 * @return Mage_Core_Controller_Front_Action|void
	 */
    public function preDispatch()
    {
        Mage::helper('connector')->auth($this->getRequest()->getParam('code'));
	    //skip order_id check for this actions
	    $skip = array('push', 'nosto');
	    $actionName = $this->getRequest()->getActionName();
        if (! in_array($actionName, $skip)) {
            $orderId = $this->getRequest()->getParam('order_id', false);
            //check for order id param
	        if ($orderId) {
                //check if order still exists
	            $order = Mage::getModel('sales/order')->load($orderId);
	            if ($order->getId()) {
		            //start app emulation
	                $storeId = $order->getStoreId();
	                $appEmulation = Mage::getSingleton('core/app_emulation');
	                $appEmulation->startEnvironmentEmulation($storeId);
                } else {
		            $message = 'Dynamic : order not found: ' . $orderId;
                    Mage::helper('connector')->log($message);
		            Mage::helper('connector')->rayLog('100', $message);
                }
            } else {
                Mage::helper('connector')->log('Dynamic : order_id missing :' . $orderId);
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
        $products = $this->getLayout()->createBlock('email_connector/recommended_products', 'connector_recommended_related', array(
            'template' => 'connector/product/list.phtml'
        ));
	    //append related products
        $this->getLayout()->getBlock('content')->append($products);

        $this->renderLayout();

    }

	/**
	 * Crosssell products.
	 */
    public function crosssellAction()
    {
        $this->loadLayout();
        $products = $this->getLayout()->createBlock('email_connector/recommended_products', 'connector_recommended_crosssell', array(
            'template' => 'connector/product/list.phtml'
        ));
	    //append crosssell products.
        $this->getLayout()->getBlock('content')->append($products);

        $this->renderLayout();
    }

	/**
	 * Upsell products.
	 */
    public function upsellAction()
    {
        $this->loadLayout();
        $products = $this->getLayout()->createBlock('email_connector/recommended_products', 'connector_recommended_upsell', array(
            'template' => 'connector/product/list.phtml'
        ));
	    //append upsell products
        $this->getLayout()->getBlock('content')->append($products);

        $this->renderLayout();
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
    }

	/**
	 * Nosto recommendation action.
	 */
	public function nostoAction()
	{
		$this->loadLayout();

		$products = $this->getLayout()->createBlock('email_connector/recommended_products', 'connector_nosto_recommended', array(
			'template' => 'connector/product/nosto.phtml'
		));
		$this->getLayout()->getBlock('content')->append($products);
		$this->renderLayout();
	}

}