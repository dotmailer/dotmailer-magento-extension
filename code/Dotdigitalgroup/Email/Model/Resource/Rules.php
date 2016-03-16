<?php

class Dotdigitalgroup_Email_Model_Resource_Rules
    extends Mage_Core_Model_Resource_Db_Abstract
{

    /**
     * constructor.
     */
    protected function _construct()
    {
        $this->_init('ddg_automation/rules', 'id');

    }
}