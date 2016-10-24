<?php

/**
 * Backend model for transactional emails host
 */
class Dotdigitalgroup_Email_Model_Adminhtml_Model_Transactional_Host extends Mage_Core_Model_Config_Data
{
    /**
     * Trim value before saving
     */
    protected function _beforeSave()
    {
        $value = trim($this->getValue());

        $this->setValue($value);
    }

}