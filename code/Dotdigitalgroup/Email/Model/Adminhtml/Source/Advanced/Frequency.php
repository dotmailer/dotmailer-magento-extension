<?php

class Dotdigitalgroup_Email_Model_Adminhtml_Source_Advanced_Frequency
{
	public function toOptionArray()
	{
		return array(
			array('value' => '1', 'label' => Mage::helper('connector')->__('1 Hour')),
			array('value' => '2', 'label' => Mage::helper('connector')->__('2 Hours')),
			array('value' => '6', 'label' => Mage::helper('connector')->__('6 Hours')),
			array('value' => '12', 'label' => Mage::helper('connector')->__('12 Hours')),
			array('value' => '24', 'label' => Mage::helper('connector')->__('24 Hours'))
		);
	}
}
