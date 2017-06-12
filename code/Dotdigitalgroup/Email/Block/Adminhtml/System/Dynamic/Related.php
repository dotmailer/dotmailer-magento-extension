<?php

class Dotdigitalgroup_Email_Block_Adminhtml_System_Dynamic_Related
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    /**
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $passcode = Mage::helper('ddg')->getPasscodeWithWarning();
        $lastOrderId = Mage::helper('ddg')->getLastOrderIdWithWarning();
        $baseUrl = Mage::helper('ddg')->generateDynamicUrl();

        //display the full url
        $text = sprintf(
            '%sconnector/products/related/code/%s/order_id/@%s@',
            $baseUrl,
            $passcode,
            $lastOrderId
        );
        $element->setData('value', $text);

        return parent::_getElementHtml($element);
    }
}