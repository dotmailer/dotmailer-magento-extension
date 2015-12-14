<?php

class Dotdigitalgroup_Email_Block_Recommended_Wishlistproducts extends Dotdigitalgroup_Email_Block_Edc
{

    protected function _getWishlistItems()
    {
        $wishlist = $this->_getWishlist();
        if($wishlist && count($wishlist->getItemCollection()))
            return $wishlist->getItemCollection();
        else
            return array();
    }

    protected function _getWishlist() {

        //customer id param
	    $customerId = Mage::app()->getRequest()->getParam('customer_id');

	    if (! $customerId)
            return array();

        //load customer wishlist collection
        $wishlistModel = Mage::getModel('wishlist/wishlist')
	        ->loadByCustomer($customerId);

	    return $wishlistModel;
    }

    /**
     * get the products to display for table
     */
    public function getLoadedProductCollection()
    {
        //display mode based on the action name
        $mode  = $this->getRequest()->getActionName();
        //number of product items to be displayed
        $limit = Mage::helper('ddg/recommended')->getDisplayLimitByMode($mode);

        $items = $this->_getWishlistItems();
        $productsToDisplay = $this->getProductsToDisplay($items, $limit, $mode, 'WISHLIST');

        return $productsToDisplay;
    }
}