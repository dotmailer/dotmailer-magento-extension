<?php

class Dotdigitalgroup_Email_Model_Resource_Wishlist extends Mage_Core_Model_Mysql4_Abstract
{
    /**
     * constructor.
     */
    protected  function _construct()
    {
        $this->_init('ddg_automation/wishlist', 'id');

    }
}