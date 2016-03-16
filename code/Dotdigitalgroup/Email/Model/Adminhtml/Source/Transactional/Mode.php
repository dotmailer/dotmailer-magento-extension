<?php

class Dotdigitalgroup_Email_Model_Adminhtml_Source_Transactional_Mode
{

    public function toOptionArray()
    {
        return array(
            'smtp' => Mage::helper('ddg')->__('SMTP')
            //'api' => Mage::helper('ddg')->__('API')
        );

    }
}