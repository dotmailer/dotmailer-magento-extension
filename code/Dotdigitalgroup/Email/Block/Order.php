<?php

class Dotdigitalgroup_Email_Block_Order extends Dotdigitalgroup_Email_Block_Edc
{

    /**
	 * Prepare layout, set template and title.
	 *
	 * @return Mage_Core_Block_Abstract|void
	 */
    protected function _prepareLayout()
    {
        if ($root = $this->getLayout()->getBlock('root')) {
            $root->setTemplate('page/blank.phtml');
        }
        if ($headBlock = $this->getLayout()->getBlock('head')) {
            $headBlock->setTitle($this->__('Order # %s', $this->getOrder()->getRealOrderId()));
        }
    }

    /**
	 * Current Order.
	 *
	 * @return Mage_Core_Model_Abstract|mixed
	 */
    public function getOrder()
    {
        $order = Mage::registry('current_order');
        if (! $order) {
            Mage::throwException(Mage::helper('ddg')->__('no current_order found for EDC'));
        }

        return $order;
    }

}