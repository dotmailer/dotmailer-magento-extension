<?php

class Dotdigitalgroup_Email_Model_Resource_Contact extends Mage_Core_Model_Mysql4_Abstract
{

	/**
	 * constructor.
	 */
	protected  function _construct()
    {
        $this->_init('email_connector/contact', 'email_contact_id');

    }
}