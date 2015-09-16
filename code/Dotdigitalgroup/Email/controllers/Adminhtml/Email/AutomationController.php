<?php

class Dotdigitalgroup_Email_Adminhtml_Email_AutomationController extends Mage_Adminhtml_Controller_Action
{

	protected function _construct(){
		$this->setUsedModuleName('Dotdigitalgroup_Email');
	}

	/**
	 * main page.
	 */
	public function indexAction()
	{
		$this->_title($this->__('Automation'))
		     ->_title($this->__('Automation Status'));
		$this->loadLayout();
		$this->_setActiveMenu('email_connector');
		$this->renderLayout();
	}

	public function editAction()
	{
		$this->_redirect('*/*');
	}

	/**
	 * main grid.
	 */
	public function gridAction(){
		$this->loadLayout();
		$this->renderLayout();
	}

	/**
	 * Delete action.
	 */
	public function massDeleteAction()
	{
		$automationIds = $this->getRequest()->getParam('automation');
		if (!is_array($automationIds)) {
			$this->_getSession()->addError($this->__('Please select .'));
		}else {
			$num = Mage::getResourceModel('ddg_automation/automation')->massDelete($automationIds);
			if(is_int($num)){
				$this->_getSession()->addSuccess(
					Mage::helper('ddg')->__('Total of %d record(s) have been deleted.', $num)
				);
			}else
				$this->_getSession()->addError($num->getMessage());
		}
		$this->_redirect('*/*/index');
	}

	/**
	 * Mark for resend.
	 */
	public function massResendAction()
	{
		$automationIds = $this->getRequest()->getParam('automation');
		if (!is_array($automationIds)) {
			$this->_getSession()->addError($this->__('Please select .'));
		}else {
			$num = Mage::getResourceModel('ddg_automation/automation')->massResend($automationIds);
			if(is_int($num)){
				$this->_getSession()->addSuccess(
					Mage::helper('ddg')->__('Total of %d record(s) have been deleted.', $num)
				);
			}else
				$this->_getSession()->addError($num->getMessage());
		}
		$this->_redirect('*/*/index');
	}

	protected function _isAllowed()
	{
		return Mage::getSingleton('admin/session')->isAllowed('email_connector/reports/email_connector_automation');
	}

}
