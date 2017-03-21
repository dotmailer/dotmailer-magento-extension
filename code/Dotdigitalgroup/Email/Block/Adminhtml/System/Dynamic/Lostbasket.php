<?php

class Dotdigitalgroup_Email_Block_Adminhtml_System_Dynamic_Lostbasket
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    /**
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        //base url for dynamic content
        $baseUrl = Mage::helper('ddg')->generateDynamicUrl();
        $passcode = Mage::helper('ddg')->getPasscodeWithWarning();
        //last quote id for dynamic page
        $lastQuoteId = Mage::helper('ddg')->getLastQuoteIdWithWarning();

        // full url
        $text = sprintf(
            "%sconnector/email/basket/code/%s/quote_id/@%s@", $baseUrl,
            $passcode, $lastQuoteId
        );

        $element->setData('value', $text);

        return parent::_getElementHtml($element);
    }

}