<?php

class Dotdigitalgroup_Email_Block_Adminhtml_System_Dynamic_Crosssell
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    /**
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $baseUrl = Mage::helper('ddg')->generateDynamicUrl();
        $passcode = Mage::helper('ddg')->getPasscodeWithWarning();
        $lastOrderId = Mage::helper('ddg')->getLastOrderIdWithWarning();

        //full url for dynamic content
        $text = sprintf(
            '%sconnector/products/crosssell/code/%s/order_id/@%s@',
            $baseUrl,
            $passcode,
            $lastOrderId
        );
        $element->setData('value', $text);

        return parent::_getElementHtml($element);
    }
}