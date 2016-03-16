<?php

class Dotdigitalgroup_Email_Model_Adminhtml_Source_Transactional_Port
{

    public function toOptionArray()
    {
        return array(
            '25'   => Mage::helper('ddg')->__("25"),
            '2525' => Mage::helper('ddg')->__("2525"),
            '587'  => Mage::helper('ddg')->__("587")
        );


    }
}