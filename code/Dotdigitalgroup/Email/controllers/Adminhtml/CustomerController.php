<?php

class Dotdigitalgroup_Email_Adminhtml_CustomerController extends Mage_Adminhtml_Controller_Action
{
    public function statAction()
    {
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('ddg_automation/adminhtml_customer_tab_stats')->toHtml()
        );
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed(
            'customer/manage'
        );
    }
}