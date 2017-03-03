<?php

class Dotdigitalgroup_Email_Model_Adminhtml_Source_Transactional_Mode
{

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            'smtp' => Mage::helper('ddg')->__('SMTP')
        );

    }
}