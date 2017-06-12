<?php

class Dotdigitalgroup_Email_Block_Adminhtml_System_Dynamic_Basket_Upsell
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    /**
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $passcode = Mage::helper('ddg')->getPasscodeWithWarning();
        $lastQuoteId = Mage::helper('ddg')->getLastQuoteIdWithWarning();
        $baseUrl = Mage::helper('ddg')->generateDynamicUrl();

        $text = sprintf(
            '%sconnector/quoteproducts/upsell/code/%s/quote_id/@%s@',
            $baseUrl,
            $passcode,
            $lastQuoteId
        );
        $element->setData('value', $text);

        return parent::_getElementHtml($element);
    }
}