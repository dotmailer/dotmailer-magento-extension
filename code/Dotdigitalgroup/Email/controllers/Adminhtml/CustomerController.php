<?php

class Dotdigitalgroup_Email_Adminhtml_CustomerController extends Mage_Adminhtml_Controller_Action
{
    public function statAction()
    {
        $block = $this->getLayout()->createBlock('ddg_automation/adminhtml_customer_tab_stats');
        echo $block->toHtml();
    }
}