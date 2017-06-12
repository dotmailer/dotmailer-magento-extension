<?php

class Dotdigitalgroup_Email_Block_Adminhtml_System_Dynamic_Upsell
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    /**
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $passcode = Mage::helper('ddg')->getPasscodeWithWarning();
        $lastOrderid = Mage::helper('ddg')->getLastOrderIdWithWarning();
        $baseUrl = Mage::helper('ddg')->generateDynamicUrl();

        $text = sprintf(
            '%sconnector/products/upsell/code/%s/order_id/@%s@',
            $baseUrl,
            $passcode,
            $lastOrderid
        );
        $element->setData('value', $text);

        return parent::_getElementHtml($element);
    }
}