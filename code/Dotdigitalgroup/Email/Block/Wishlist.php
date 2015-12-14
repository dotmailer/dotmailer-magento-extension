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

    protected function _getWishlist() {

	    //customer id param
	    $customerId = Mage::app()->getRequest()->getParam('customer_id', false);

	    if (! $customerId)
            return false;

        $wishlistModel = Mage::getModel('wishlist/wishlist')->loadByCustomer($customerId);

	    return $wishlistModel;
    }

    public function getMode()
    {
        return Mage::helper('ddg')->getWebsiteConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_DYNAMIC_CONTENT_WIHSLIST_DISPLAY
        );
    }
}