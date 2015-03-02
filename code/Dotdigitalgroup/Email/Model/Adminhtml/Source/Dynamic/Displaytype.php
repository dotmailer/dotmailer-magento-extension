<?php

class Dotdigitalgroup_Email_Model_Adminhtml_Source_Dynamic_Displaytype
{
	/**
	 * Display type mode.
	 *
	 * @return array
	 */
	public function toOptionArray()
    {
        return array(
            array('value' => 'grid', 'label' => Mage::helper('ddg')->__('Grid')),
            array('value' => 'list', 'label' => Mage::helper('ddg')->__('List'))
        );

    }
}