<?php
require_once 'Dotdigitalgroup' . DS . 'Email' . DS . 'controllers' . DS . 'ResponseController.php';

class Dotdigitalgroup_Email_DynamicContentController extends Dotdigitalgroup_Email_ResponseController
{
	/**
	 * @return Mage_Core_Controller_Front_Action|void
	 * @throws Exception
	 */
	public function preDispatch()
	{
		//authenticate
		Mage::helper('connector')->auth($this->getRequest()->getParam('code'));
		$orderId = $this->getRequest()->getParam('order_id', false);
		//check for order_id param
		if ($orderId) {
			$order = Mage::getModel('sales/order')->load($orderId);
			//check if the order still exists
			if ($order->getId()) {
				$storeId = $order->getStoreId();
				//start the emulation for order store
				$appEmulation = Mage::getSingleton('core/app_emulation');
				$appEmulation->startEnvironmentEmulation($storeId);
			} else {
				throw new Exception('TE invoice : order not found: ' . $orderId);
			}
		} else {
			throw new Exception('TE invoice : order_id missing :' . $orderId);
		}
		parent::preDispatch();
	}
}