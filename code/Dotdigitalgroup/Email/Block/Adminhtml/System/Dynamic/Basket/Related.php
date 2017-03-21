<?php

class Dotdigitalgroup_Email_Block_Adminhtml_System_Dynamic_Basket_Related
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    /**
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        //passcode to append for url
        $passcode = Mage::helper('ddg')->getPasscodeWithWarning();
        //last quote id for dynamic page
        $lastQuoteId = Mage::helper('ddg')->getLastQuoteIdWithWarning();
        //generate the base url and display for default store id
        $baseUrl = Mage::helper('ddg')->generateDynamicUrl();

        //display the full url
        $text = sprintf(
            '%sconnector/quoteproducts/related/code/%s/quote_id/@%s@', $baseUrl,
            $passcode, $lastQuoteId
        );
        $element->setData('value', $text);

        return parent::_getElementHtml($element);
    }
}