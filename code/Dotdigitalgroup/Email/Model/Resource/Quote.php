<?php

class Dotdigitalgroup_Email_Model_Resource_Quote extends Mage_Core_Model_Resource_Db_Abstract
{
    /**
     * constructor.
     */
    protected function _construct()
    {
        $this->_init('ddg_automation/quote', 'id');
    }
}