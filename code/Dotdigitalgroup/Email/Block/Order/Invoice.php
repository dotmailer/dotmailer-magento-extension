<?php
class Dotdigitalgroup_Email_Block_Order_Invoice extends Mage_Sales_Block_Order_Invoice_Items
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
	 * Get current order.
	 * @return Mage_Sales_Model_Order
	 * @throws Exception
	 */
    public function getOrder()
    {
        $order = Mage::registry('current_order');
        if (! $order) {
            Mage::throwException(Mage::helper('ddg')->__('no current_order found for EDC'));
        }

        if (! $order->hasInvoices()) {
            //throw new Exception('TE - no invoice for order : '. $orderId);
            Mage::helper('ddg')->log('TE - no invoice for order : '. $order->getId());
            return false;
        }

        return $order;
    }

}
