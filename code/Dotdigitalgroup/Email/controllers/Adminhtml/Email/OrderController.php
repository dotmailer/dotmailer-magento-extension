<?php

class Dotdigitalgroup_Email_Adminhtml_Email_OrderController extends Mage_Adminhtml_Controller_Action
{
	/**
	 * main page.
	 */
	public function indexAction()
    {
        $this->loadLayout();
        $this->_setActiveMenu('email_connector');
        $this->_addContent($this->getLayout()->createBlock('email_connector/adminhtml_order'));
        $this->getLayout()->getBlock('head')->setTitle('Connector Orders');
        $this->renderLayout();
    }

    /**
     * Check currently called action by permissions for current user
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('newsletter/email_connector/email_connector_order');
    }
}