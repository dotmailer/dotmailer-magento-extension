<?php

class Dotdigitalgroup_Email_Model_Resource_Quote_Collection
    extends Mage_Core_Model_Resource_Db_Collection_Abstract
{

    /**
     * Constructor.
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('ddg_automation/quote');
    }
}