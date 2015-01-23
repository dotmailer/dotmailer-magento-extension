<?php

class Dotdigitalgroup_Email_Model_Adminhtml_Source_Transactional_Sendtype
{

    /**
     * send type options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = array(
            array('value' => Dotdigitalgroup_Email_Helper_Transactional::TRANSACTIONAL_SENDTYPE_SYSTEM_DEFAULT, 'label' => Mage::helper('connector')->__('-- Use system default --')),
            array('value' => Dotdigitalgroup_Email_Helper_Transactional::TRANSACTIONAL_SENDTYPE_VIA_CONNECTOR, 'label' => Mage::helper('connector')->__('-- Send via connector --')),
            array('value' => Dotdigitalgroup_Email_Helper_Transactional::TRANSACTIOANL_SNEDTYPE_DESIGN_VIA_CONNECTOR, 'label' => Mage::helper('connector')->__('-- Design + Send via connector --'))
        );

        return $options;
    }
}