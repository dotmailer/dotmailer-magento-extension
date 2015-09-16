<?php

class Dotdigitalgroup_Email_Block_Wishlist extends Dotdigitalgroup_Email_Block_Edc
{
    protected $_website;

    public function getWishlistItems()
    {
        $wishlist = $this->_getWishlist();
        if($wishlist && count($wishlist->getItemCollection()))
            return $wishlist->getItemCollection();
        else
            return false;
    }

    protected function _getWishlist()
    {
        $customerId = Mage::app()->getRequest()->getParam('customer_id');
        if(!$customerId)
            return false;

        $customer = Mage::getModel('customer/customer')->load($customerId);
        if(!$customer->getId())
            return false;

        $collection = Mage::getModel('wishlist/wishlist')->getCollection();
        $collection->addFieldToFilter('customer_id', $customerId)
                    ->setOrder('updated_at', 'DESC');

        if ($collection->count())
            return $collection->setPageSize(1)->setCurPage(1)->getFirstItem();
        else
            return false;

    }

    public function getMode()
    {
        return Mage::helper('ddg')->getWebsiteConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_DYNAMIC_CONTENT_WIHSLIST_DISPLAY
        );
    }
}