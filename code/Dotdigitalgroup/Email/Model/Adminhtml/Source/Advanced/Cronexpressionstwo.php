<?php

class Dotdigitalgroup_Email_Model_Adminhtml_Source_Advanced_Cronexpressionstwo
{

    public function toOptionArray()
    {
        return array(
            array('value' => '*/15 * * * *',
                  'label' => Mage::helper('ddg')->__('Every 15 Minutes')),
            array('value' => '*/30 * * * *',
                  'label' => Mage::helper('ddg')->__('Every 30 Minutes')),
            array('value' => '00 * * * *',
                  'label' => Mage::helper('ddg')->__('Every 60 Minutes')),
        );
    }
}
