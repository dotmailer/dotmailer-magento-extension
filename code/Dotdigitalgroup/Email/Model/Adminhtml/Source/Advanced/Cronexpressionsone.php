<?php

class Dotdigitalgroup_Email_Model_Adminhtml_Source_Advanced_Cronexpressionsone
{

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => '*/5 * * * *',
                  'label' => Mage::helper('ddg')->__('Every 5 Minutes')),
            array('value' => '*/10 * * * *',
                  'label' => Mage::helper('ddg')->__('Every 10 Minutes')),
            array('value' => '*/15 * * * *',
                  'label' => Mage::helper('ddg')->__('Every 15 Minutes')),
        );
    }
}
