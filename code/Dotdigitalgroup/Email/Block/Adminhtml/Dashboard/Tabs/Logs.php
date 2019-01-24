<?php

class Dotdigitalgroup_Email_Block_Adminhtml_Dashboard_Tabs_Logs
    extends Mage_Adminhtml_Block_Widget
    implements Mage_Adminhtml_Block_Widget_Tab_Interface
{

    /**
     * Set the template.
     */
    public function _construct()
    {
        parent::_construct();

        $this->setTemplate('connector/dashboard/logs.phtml');
    }

    public function canShowTab()
    {
        return true;
    }

    public function isHidden()
    {
        return true;
    }

    public function getTabLabel()
    {
        return Mage::helper('ddg')->__('Engagement Cloud Logs');
    }

    public function getTabTitle()
    {
        return Mage::helper('ddg')->__('Engagement Cloud Logs');
    }
}