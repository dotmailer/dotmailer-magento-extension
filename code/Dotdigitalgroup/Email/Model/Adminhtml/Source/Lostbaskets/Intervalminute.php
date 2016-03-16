<?php

class Dotdigitalgroup_Email_Model_Adminhtml_Source_Lostbaskets_Intervalminute
{

    /**
     * lost basket hour options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => "15",
                  'label' => Mage::helper('ddg')->__('15 Minutes')),
            array('value' => "20",
                  'label' => Mage::helper('ddg')->__('20 Minutes')),
            array('value' => "25",
                  'label' => Mage::helper('ddg')->__('25 Minutes')),
            array('value' => "30",
                  'label' => Mage::helper('ddg')->__('30 Minutes')),
            array('value' => "40",
                  'label' => Mage::helper('ddg')->__('40 Minutes')),
            array('value' => "50",
                  'label' => Mage::helper('ddg')->__('50 Minutes')),
            array('value' => "60",
                  'label' => Mage::helper('ddg')->__('60 Minutes')),
        );
    }
}