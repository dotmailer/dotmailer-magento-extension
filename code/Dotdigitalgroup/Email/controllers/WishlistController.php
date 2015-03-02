<?php
require_once 'Dotdigitalgroup' . DS . 'Email' . DS . 'controllers' . DS . 'ResponseController.php';

class Dotdigitalgroup_Email_WishlistController extends Dotdigitalgroup_Email_ResponseController
{
    /**
     * Related products.
     */
    public function relatedAction()
    {
        $this->loadLayout();
        $products = $this->getLayout()->createBlock('ddg_automation/recommended_wishlistproducts', 'connector_recommended_wishlist_related', array(
            'template' => 'connector/product/list.phtml'
        ));
        //append related products
        $this->getLayout()->getBlock('content')->append($products);
        $this->renderLayout();
        $this->checkContentNotEmpty($this->getLayout()->getOutput());
    }

    /**
     * Crosssell products.
     */
    public function crosssellAction()
    {
        $this->loadLayout();
        $products = $this->getLayout()->createBlock('ddg_automation/recommended_wishlistproducts', 'connector_recommended_wishlist_crosssell', array(
            'template' => 'connector/product/list.phtml'
        ));
        //append crosssell products.
        $this->getLayout()->getBlock('content')->append($products);
        $this->renderLayout();
        $this->checkContentNotEmpty($this->getLayout()->getOutput());
    }

    /**
     * Upsell products.
     */
    public function upsellAction()
    {
        $this->loadLayout();
        $products = $this->getLayout()->createBlock('ddg_automation/recommended_wishlistproducts', 'connector_recommended_wishlist_upsell', array(
            'template' => 'connector/product/list.phtml'
        ));
        //append upsell products
        $this->getLayout()->getBlock('content')->append($products);
        $this->renderLayout();
        $this->checkContentNotEmpty($this->getLayout()->getOutput());
    }
}