<?php

class Dotdigitalgroup_Email_Model_Resource_Consent extends Mage_Core_Model_Resource_Db_Abstract
{
    /**
     * Constructor.
     */
    protected function _construct()
    {
        $this->_init('ddg_automation/consent', 'id');
    }

    /**
     * Delete Consent for contact.
     *
     * @param $emails
     * @return array
     */
    public function deleteConsentByEmails($emails)
    {
        if (empty($emails)) {
            return array();
        }
        $collection = Mage::getModel('ddg_automation/consent')->getCollection();
        $collection->getSelect()
            ->joinInner(
                array('c' => $this->getTable('ddg_automation/contact')),
                "c.email_contact_id = main_table.email_contact_id",
                array()
            );

        $collection->addFieldToFilter('c.email', array('in' => $emails));

        return $collection->walk('delete');
    }
}