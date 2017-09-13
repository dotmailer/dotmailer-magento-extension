<?php

class Dotdigitalgroup_Email_Model_Resource_Abandoned extends Mage_Core_Model_Resource_Db_Abstract
{
    /**
     * Constructor.
     */
    protected function _construct()
    {
        $this->_init('ddg_automation/abandoned', 'id');
    }

}