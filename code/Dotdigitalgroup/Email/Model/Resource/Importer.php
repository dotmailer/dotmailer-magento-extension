<?php

class Dotdigitalgroup_Email_Model_Resource_Importer extends Mage_Core_Model_Resource_Db_Abstract
{
    /**
     * cosntructor.
     */
    protected function _construct()
    {
        $this->_init('ddg_automation/importer', 'id');
    }
}