<?php

class Dotdigitalgroup_Email_Adminhtml_Email_WishlistController extends Mage_Adminhtml_Controller_Action
{
    /**
     * main page.
     */
    public function indexAction()
    {
        $this->loadLayout();
        $this->_setActiveMenu('ddg_automation');
        $this->_addContent($this->getLayout()->createBlock('ddg_automation/adminhtml_wishlist'));
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
        return Mage::getSingleton('admin/session')->isAllowed('email_connector/reports/email_connector_wishlist');
    }
}
