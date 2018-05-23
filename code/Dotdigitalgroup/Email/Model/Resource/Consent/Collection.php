<?php

class Dotdigitalgroup_Email_Model_Resource_Consent_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    /**
     * Constructor.
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('ddg_automation/consent');
    }

    /**
     * Load consent by email contact id.
     *
     * @param int $contactId
     *
     * @return $this
     */
    public function loadByEmailContactId($contactId)
    {
        $this->addFieldToFilter('email_contact_id', $contactId);

        return $this;
    }

}