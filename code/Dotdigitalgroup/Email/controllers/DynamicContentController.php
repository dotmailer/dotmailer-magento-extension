<?php
require_once 'Dotdigitalgroup' . DS . 'Email' . DS . 'controllers' . DS
    . 'ResponseController.php';

class Dotdigitalgroup_Email_DynamicContentController
    extends Dotdigitalgroup_Email_ResponseController
{

    /**
     * @return Mage_Core_Controller_Front_Action|void
     * @throws Exception
     */
    public function preDispatch()
    {
        //authenticate
        $this->authenticate();

        $orderId = $this->getRequest()->getParam('order_id', false);
        //check for order_id param
        if ($orderId) {
            $order = Mage::getModel('sales/order')->load($orderId);
            //check if the order still exists
            if ($order->getId()) {
                Mage::register('current_order', $order);
                $storeId = $order->getStoreId();
                //start the emulation for order store
                $appEmulation = Mage::getSingleton('core/app_emulation');
                $appEmulation->startEnvironmentEmulation($storeId);
            } else {
                Mage::helper('ddg')->log('order not found: ' . $orderId);
                $this->sendResponse();
                Mage::throwException(
                    Mage::helper('ddg')->__('Order not found')
                );
            }
        } else {
            Mage::helper('ddg')->log('order_id missing :' . $orderId);
            $this->sendResponse();
            Mage::throwException(
                Mage::helper('ddg')->__('Order id is missing')
            );
        }

        parent::preDispatch();
    }
}