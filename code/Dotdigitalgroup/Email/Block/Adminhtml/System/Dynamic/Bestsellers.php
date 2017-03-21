<?php

class Dotdigitalgroup_Email_Block_Adminhtml_System_Dynamic_Bestsellers
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    /**
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        //base url
        $baseUrl = Mage::helper('ddg')->generateDynamicUrl();

        //config passcode
        $passcode = Mage::helper('ddg')->getPasscodeWithWarning();

        //full url
        $text = sprintf(
            '%sconnector/report/bestsellers/code/%s', $baseUrl, $passcode
        );
        $element->setData('value', $text);
        $element->setData('disabled', 'disabled');

        return parent::_getElementHtml($element);
    }
}