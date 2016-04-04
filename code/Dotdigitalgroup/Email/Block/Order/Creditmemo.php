<?php

class Dotdigitalgroup_Email_Block_Order_Creditmemo
    extends Mage_Sales_Block_Order_Creditmemo_Items
{

    /**
     * Prepare layout.
     *
     * @return Mage_Core_Block_Abstract|void
     */
    protected function _prepareLayout()
    {
        if ($root = $this->getLayout()->getBlock('root')) {
            $root->setTemplate('page/blank.phtml');
        }
    }

    /**
     * Get current Order.
     *
     * @return Mage_Sales_Model_Order
     * @throws Exception
     */
    public function getOrder()
    {
        $order = Mage::registry('current_order');
        if ( ! $order) {
            Mage::throwException(
                Mage::helper('ddg')->__('no current_order found for EDC')
            );
        }

        if ( ! $order->hasCreditmemos()) {
            
            Mage::helper('ddg')->log(
                'TE - no creditmemo for order : ' . $order->getId()
            );

            return false;
        }

        return $order;
    }

    /**
     * Order items.
     *
     * @return mixed
     */
    public function getCreditmemoItems()
    {
        return Mage::registry('current_order')->getItemsCollection();
    }
}
