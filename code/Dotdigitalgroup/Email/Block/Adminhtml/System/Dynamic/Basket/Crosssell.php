<?php

class Dotdigitalgroup_Email_Block_Adminhtml_System_Dynamic_Basket_Crosssell
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
        $lastQuoteId = Mage::helper('ddg')->getLastQuoteIdWithWarning();

        //full url for dynamic content
        $text = sprintf(
            '%sconnector/quoteproducts/crosssell/code/%s/quote_id/@%s@',
            $baseUrl,
            $passcode,
            $lastQuoteId
        );
        $element->setData('value', $text);

        return parent::_getElementHtml($element);
    }
}