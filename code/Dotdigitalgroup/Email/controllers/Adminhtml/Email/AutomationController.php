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
			try {
				foreach ($automationIds as $id) {
					$automation = Mage::getSingleton('ddg_automation/automation')->load($id);
					Mage::dispatchEvent('connector_controller_automation_delete', array('automation' => $automation));
					$automation->delete();
				}
				$this->_getSession()->addSuccess(
					Mage::helper('ddg')->__('Total of %d record(s) have been deleted.', count($automationIds))
				);
			} catch (Exception $e) {
				$this->_getSession()->addError($e->getMessage());
			}
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
			try {

				/** @var $coreResource Mage_Core_Model_Resource */
				$coreResource = Mage::getSingleton('core/resource');

				/** @var $conn Varien_Db_Adapter_Pdo_Mysql */
				$conn = $coreResource->getConnection('core_write');

				$num = $conn->update($coreResource->getTableName('ddg_automation/automation'),
					array('enrolment_status' => new Zend_Db_Expr('null')),
					array('id IN(?)' => $automationIds)
				);

				$this->_getSession()->addSuccess(
					Mage::helper('ddg')->__('Total of %d record(s) have been deleted.', $num)
				);
			} catch (Exception $e) {
				$this->_getSession()->addError($e->getMessage());
			}
		}
		$this->_redirect('*/*/index');
	}

	protected function _isAllowed()
	{
		return Mage::getSingleton('admin/session')->isAllowed('email_connector/reports/email_connector_automation');
	}

}
