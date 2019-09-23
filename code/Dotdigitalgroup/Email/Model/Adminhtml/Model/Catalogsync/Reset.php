<?php

class Dotdigitalgroup_Email_Model_Adminhtml_Model_Catalogsync_Reset extends Mage_Core_Model_Config_Data
{
    protected function _afterSave()
    {
        if ($this->isValueChanged()) {
            Mage::getResourceModel('ddg_automation/catalog')->reset();
        }
    }
}