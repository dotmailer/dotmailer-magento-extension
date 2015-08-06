<?php

class Dotdigitalgroup_Email_Model_Resource_Importer_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    /**
     * constructor.
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('ddg_automation/importer');
    }
}