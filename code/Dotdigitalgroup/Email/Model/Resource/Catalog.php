<?php

class Dotdigitalgroup_Email_Model_Resource_Catalog extends Mage_Core_Model_Mysql4_Abstract
{
    /**
     * constructor.
     */
    protected  function _construct()
    {
        $this->_init('ddg_automation/catalog', 'id');

    }
}