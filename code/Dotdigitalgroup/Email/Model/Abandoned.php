<?php

class Dotdigitalgroup_Email_Model_Abandoned extends Mage_Core_Model_Abstract
{

    /**
     * Constructor.
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('ddg_automation/abandoned');
    }

}