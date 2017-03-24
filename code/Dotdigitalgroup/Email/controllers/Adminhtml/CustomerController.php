<?php

class Dotdigitalgroup_Email_Adminhtml_CustomerController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Customer tab stats.
     */
    public function statAction()
    {
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('ddg_automation/adminhtml_customer_tab_stats')->toHtml()
        );
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed(
            'customer/manage'
        );
    }
}