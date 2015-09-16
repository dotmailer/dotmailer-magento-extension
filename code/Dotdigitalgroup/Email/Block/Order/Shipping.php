<?php
class Dotdigitalgroup_Email_Block_Order_Shipping  extends Mage_Sales_Block_Order_Creditmemo_Items
{
    /**
	 * Prepare layout.
	 * @return Mage_Core_Block_Abstract|void
	 */
    protected function _prepareLayout()
    {
        if ($root = $this->getLayout()->getBlock('root')) {
            $root->setTemplate('page/blank.phtml');
        }
    }

	/**
	 * @return Mage_Sales_Model_Order
	 * @throws Exception
	 */
    public function getOrder()
    {
        $order = Mage::registry('current_order');
        if (! $order) {
            Mage::throwException('no current_order found for EDC');
        }

        if (! $order->hasShipments()) {
            //throw new Exception('TE - no shipments for order : '. $orderId);
            Mage::helper('ddg')->log('TE - no shipments for order : '. $order->getId());
            return false;
        }

        return $order;

    }

}
