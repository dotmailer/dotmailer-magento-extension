<?php

class Dotdigitalgroup_Email_Block_Recommended_Wishlistproducts extends Dotdigitalgroup_Email_Block_Edc
{

    /**
     * Wishlist items.
     * 
     * @return array|Mage_Wishlist_Model_Mysql4_Item_Collection
     */
    protected function getWishlistItems()
    {
        $wishlist = $this->getWishlist();
        //@codingStandardsIgnoreStart
        if ($wishlist && count($wishlist->getItemCollection())) {
            return $wishlist->getItemCollection();
        } else {
            return array();
        }
        //@codingStandardsIgnoreEnd
    }

    /**
     * Get wishlist for customer.
     * 
     * @return array|Mage_Wishlist_Model_Wishlist
     */
    protected function getWishlist()
    {
        //customer id param
        $customerId = Mage::app()->getRequest()->getParam('customer_id');

        if (!$customerId) {
            return array();
        }

        //load customer wishlist collection
        $wishlistModel = Mage::getModel('wishlist/wishlist')
            ->loadByCustomer($customerId);

        return $wishlistModel;
    }

    /**
     * Get the products to display for table.
     * @return array
     * @throws Exception
     */
    public function getLoadedProductCollection()
    {
        //display mode based on the action name
        $mode = $this->getRequest()->getActionName();
        //number of product items to be displayed
        $limit = Mage::helper('ddg/recommended')->getDisplayLimitByMode($mode);

        $items = $this->getWishlistItems();
        $productsToDisplay = $this->getProductsToDisplay(
            $items, $limit, $mode, 'WISHLIST'
        );

        return $productsToDisplay;
    }
}