<?php

class Dotdigitalgroup_Email_Model_Adminhtml_Source_Datamapping_Datafieldaccess
{
	/**
	 * @return array
	 */
	public function toOptionArray()
	{
		$dataType = array(
			array('value' => 'Private', 'label' => Mage::helper('connector')->__('Private')),
            array('value' => 'Public',  'label' => Mage::helper('connector')->__('Public')),
		);

		return $dataType;
	}
}