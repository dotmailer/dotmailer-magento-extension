<?php

class Dotdigitalgroup_Email_Block_System_Email_Template_Edit extends Mage_Adminhtml_Block_System_Email_Template_Edit
{
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('connector/system/email/template/edit.phtml');
    }

    public function getConnectorTemplates()
    {
        return Mage::helper('ddg')->getTemplateList();
    }
}