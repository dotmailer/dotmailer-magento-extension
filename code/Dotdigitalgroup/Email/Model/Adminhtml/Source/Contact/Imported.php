<?php

class Dotdigitalgroup_Email_Model_Adminhtml_Source_Contact_Imported
{
	/**
	 * Contact imported options.
	 *
	 * @return array
	 */
	public function getOptions()
    {
        return array(
            '1' =>  Mage::helper('connector')->__('Imported'),
            'null' => Mage::helper('connector')->__('Not Imported'),
        );
    }
}