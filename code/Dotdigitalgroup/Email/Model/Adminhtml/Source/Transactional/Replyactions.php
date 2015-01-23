<?php

class Dotdigitalgroup_Email_Model_Adminhtml_Source_Transactional_Replyactions
{

	/**
	 * Returns all reply actions for campaign
	 *
	 * @return array
	 */
	public function toOptionArray()
    {
        $fields = array(
            array('value' => '0', 'label' => Mage::helper('connector')->__('-- Please select --')),
            array('value' => 'WebMailForward', 'label' => Mage::helper('connector')->__('Report + Forward')),
            array('value' => 'Webmail', 'label' => Mage::helper('connector')->__('Report')),
            array('value' => 'Delete', 'label' => Mage::helper('connector')->__('Delete'))
        );
        return $fields;
    }

}