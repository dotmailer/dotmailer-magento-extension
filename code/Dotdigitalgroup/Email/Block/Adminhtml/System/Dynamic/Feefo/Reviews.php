<?php

class Dotdigitalgroup_Email_Block_Adminhtml_System_Dynamic_Feefo_Reviews
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    /**
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        //passcode to append for url
        $passcode = Mage::helper('ddg')->getPasscode();

        if ($passcode == '') {
            $passcode = '[PLEASE SET UP A PASSCODE]';
        }

        //generate the base url and display for default store id
        $baseUrl = Mage::helper('ddg')->generateDynamicUrl();

        //display the full url
        $text = sprintf(
            '%sconnector/feefo/reviews/code/%s/quote_id/@QUOTE_ID@', $baseUrl,
            $passcode
        );
        $element->setData('value', $text);

        return parent::_getElementHtml($element);
    }
}