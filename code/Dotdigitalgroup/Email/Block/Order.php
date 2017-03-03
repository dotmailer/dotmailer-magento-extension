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
            $headBlock->setTitle(
                $this->__('Order # %s', $this->getOrder()->getRealOrderId())
            );
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
            Mage::throwException(
                Mage::helper('ddg')->__('no current_order found for EDC')
            );
        }

        return $order;
    }

    /**
     * Dysplay mode.
     *
     * @return string
     */
    public function getMode()
    {
        $website = Mage::app()->getStore($this->getOrder()->getStoreId())
            ->getWebsite();
        $mode    = Mage::helper('ddg')->getReviewDisplayType($website);

        return $mode;
    }

    /**
     * Order website.
     *
     * @return Mage_Core_Model_Website
     */
    public function getWebsite()
    {
        return Mage::app()->getStore($this->getOrder()->getStoreId())
            ->getWebsite();
    }

    /**
     * Product items to display.
     *
     * @return Mage_Catalog_Model_Resource_Product_Collection
     */
    public function getItems()
    {
        $order = $this->getOrder();
        $items = $order->getAllVisibleItems();
        $productIds = array();
        //get the product ids for the collection
        foreach ($items as $item) {
            $productIds[] = $item->getProductId();
        }

        $items = Mage::getModel('catalog/product')->getCollection()
            ->addAttributeToSelect('*')
            ->addFieldToFilter('entity_id', array('in' => $productIds));

        return $items;
    }
}