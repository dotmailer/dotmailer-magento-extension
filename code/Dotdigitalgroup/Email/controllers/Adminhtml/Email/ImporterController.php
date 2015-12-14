<?php

class Dotdigitalgroup_Email_Adminhtml_Email_ImporterController extends Mage_Adminhtml_Controller_Action
{
    /**
     * main page.
     */
    public function indexAction()
    {
        $this->loadLayout();
        $this->_setActiveMenu('email_connector');
        $this->getLayout()->getBlock('head')->setTitle('Importer Status');
        $this->renderLayout();
    }

	/**
	 * Mark a contact to be resend.
	 */
	public function massResendAction()
	{
		$ids = $this->getRequest()->getParam('importer');
		if (!is_array($ids)) {
			$this->_getSession()->addError($this->__('Please select import.'));
		}else {
			$num = Mage::getResourceModel('ddg_automation/importer')->massResend($ids);
			if(is_int($num)) {
				$this->_getSession()->addSuccess(
					Mage::helper('ddg')->__('Total of %d record(s) set for reset.', $num)
				);
			}else
				$this->_getSession()->addError($num->getMessage());
		}
		$this->_redirect('*/*/index');
	}


	/**
	 * Mass delete contacts.
	 */
	public function massDeleteAction()
	{
		$ids = $this->getRequest()->getParam('importer');
		if (!is_array($ids)) {
			$this->_getSession()->addError($this->__('Please select import.'));
		}else {
			$num = Mage::getResourceModel('ddg_automation/importer')->massDelete($ids);
			if (is_int($num)){
				$this->_getSession()->addSuccess(
					Mage::helper('ddg')->__('Total of %d record(s) have been deleted.', $num)
				);
			}else
				$this->_getSession()->addError($num->getMessage());
		}
		$this->_redirect('*/*/index');
	}

    /**
     * Check currently called action by permissions for current user
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('email_connector/reports/email_connector_importer');
    }
}
