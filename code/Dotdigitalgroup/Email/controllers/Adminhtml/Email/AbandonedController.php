<?php

class Dotdigitalgroup_Email_Adminhtml_Email_AbandonedController
    extends Mage_Adminhtml_Controller_Action
{

    /**
     * Constructor.
     */
    protected function _construct()
    {
        $this->setUsedModuleName('Dotdigitalgroup_Email');
    }

    /**
     * Main page.
     */
    public function indexAction()
    {
        $this->_title($this->__('Reports'))
            ->_title($this->__('Abandoned Carts'));
        $this->loadLayout();
        $this->_setActiveMenu('email_connector');
        $this->renderLayout();
    }

    /**
     * Main grid.
     */
    public function gridAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    /**
     * Delete action.
     */
    public function massDeleteAction()
    {
        $abandoned = $this->getRequest()->getParam('abandoned');
        if (!is_array($abandoned)) {
            $this->_getSession()->addError($this->__('Please select .'));
        } else {
            $num = Mage::getResourceModel('ddg_automation/abandoned')
                ->massDelete($abandoned);

            if (is_int($num)) {
                $this->_getSession()->addSuccess(
                    Mage::helper('ddg')->__(
                        'Total of %d record(s) have been deleted.', $num
                    )
                );
            } else {
                $this->_getSession()->addError($num->getMessage());
            }
        }

        $this->_redirect('*/*/index');
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed(
            'email_connector/reports/email_connector_abandoned'
        );
    }
}
