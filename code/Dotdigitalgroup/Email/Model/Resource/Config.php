<?php

class Dotdigitalgroup_Email_Model_Resource_Config extends Mage_Core_Model_Mysql4_Abstract
{

	/**
	 * constructor.
	 */
	protected  function _construct()
    {
        $this->_init('ddg_automation/config', 'email_config_id');

    }
}