<?php
require_once 'Dotdigitalgroup' . DS . 'Email' . DS . 'controllers' . DS
    . 'ResponseController.php';

class Dotdigitalgroup_Email_ProductsController
    extends Dotdigitalgroup_Email_ResponseController
{

    /**
     * @return Mage_Core_Controller_Front_Action|void
     */
    public function preDispatch()
    {
        //authenticate
        $this->authenticate();
        //skip order_id check for this actions
        $skip       = array('push');
        $actionName = $this->getRequest()->getActionName();
        if (! in_array($actionName, $skip)) {
            $orderId = $this->getRequest()->getParam('order_id', false);
            //check for order id param
            if ($orderId) {
                //check if order still exists
                $order = Mage::getModel('sales/order')->load($orderId);
                if ($order->getId()) {
                    Mage::register('current_order', $order);
                    //start app emulation
                    $storeId      = $order->getStoreId();
                    $appEmulation = Mage::getSingleton('core/app_emulation');
                    $appEmulation->startEnvironmentEmulation($storeId);
                } else {
                    $message = 'Dynamic : order not found: ' . $orderId;
                    Mage::helper('ddg')->log($message)
                        ->rayLog($message);
                }
            } else {
                Mage::helper('ddg')->log(
                    'Dynamic : order_id missing :' . $orderId
                );
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

        $this->renderLayout();
    }

    /**
     * Crosssell products.
     */
    public function crosssellAction()
    {
        $this->loadLayout();

        $this->renderLayout();
    }

    /**
     * Upsell products.
     */
    public function upsellAction()
    {
        $this->loadLayout();

        $this->renderLayout();
    }

    /**
     * Products that are set to manually push as related.
     */
    public function pushAction()
    {
        $this->loadLayout();

        $this->renderLayout();
    }
}