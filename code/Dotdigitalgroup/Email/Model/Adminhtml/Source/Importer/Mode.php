<?php

class Dotdigitalgroup_Email_Model_Adminhtml_Source_Importer_Mode
{
    /**
     * Contact imported options.
     *
     * @return array
     */
    public function getOptions()
    {
        return array(
            Dotdigitalgroup_Email_Model_Importer::MODE_BULK => Mage::helper('ddg')->__('Bulk'),
            Dotdigitalgroup_Email_Model_Importer::MODE_SINGLE => Mage::helper('ddg')->__('Single'),
            Dotdigitalgroup_Email_Model_Importer::MODE_SINGLE_DELETE => Mage::helper('ddg')->__('Single Delete'),
            Dotdigitalgroup_Email_Model_Importer::MODE_CONTACT_DELETE => Mage::helper('ddg')->__('Contact Delete'),
            Dotdigitalgroup_Email_Model_Importer::MODE_SUBSCRIBER_RESUBSCRIBED => Mage::helper('ddg')->__('Subscriber Resubscribed'),
            Dotdigitalgroup_Email_Model_Importer::MODE_SUBSCRIBER_UPDATE => Mage::helper('ddg')->__('Subscriber Update'),
            Dotdigitalgroup_Email_Model_Importer::MODE_CONTACT_EMAIL_UPDATE => Mage::helper('ddg')->__('Contact Email Update')
        );
    }
}