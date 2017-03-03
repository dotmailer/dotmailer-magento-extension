<?php

class Dotdigitalgroup_Email_Block_Wishlist
    extends Dotdigitalgroup_Email_Block_Edc
{

    /**
     * @var
     */
    public $website;

    /**
     * @return bool|Mage_Wishlist_Model_Mysql4_Item_Collection
     */
    public function getWishlistItems()
    {
        $wishlist = $this->_getWishlist();

        if ($wishlist && ! empty($wishlist->getItemCollection())) {
            return $wishlist->getItemCollection();
        } else {
            return false;
        }
    }

    /**
     * @return bool|Mage_Wishlist_Model_Wishlist
     */
    protected function _getWishlist()
    {
        //customer id param
        $customerId = Mage::app()->getRequest()->getParam('customer_id', false);

        if (! $customerId) {
            return false;
        }

        $wishlistModel = Mage::getModel('wishlist/wishlist')->loadByCustomer(
            $customerId
        );

        return $wishlistModel;
    }

    /**
     * @return mixed
     */
    public function getMode()
    {
        return Mage::helper('ddg')->getWebsiteConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_DYNAMIC_CONTENT_WIHSLIST_DISPLAY
        );
    }
}