<?php

class Dotdigitalgroup_Email_Adminhtml_Email_WishlistController extends Mage_Adminhtml_Controller_Action
{
    /**
     * main page.
     */
    public function indexAction()
    {
        $this->loadLayout();
        $this->_setActiveMenu('email_connector');
        $this->_addContent($this->getLayout()->createBlock('email_connector/adminhtml_wishlist'));
        $this->getLayout()->getBlock('head')->setTitle('Connector Wishlist(s)');
        $this->renderLayout();
    }

    /**
     * Check currently called action by permissions for current user
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('newsletter/email_connector/email_connector_wishlist');
    }
}
