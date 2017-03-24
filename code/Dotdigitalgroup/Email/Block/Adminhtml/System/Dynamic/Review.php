<?php

class Dotdigitalgroup_Email_Block_Adminhtml_System_Dynamic_Review
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
        //last order id witch information will be generated
        $lastOrderId = Mage::helper('ddg')->getLastOrderId();

        if ($passcode == '') {
            $passcode = '[PLEASE SET UP A PASSCODE]';
        }

        if (!$lastOrderId) {
            $lastOrderId = '[PLEASE MAP THE LAST ORDER ID]';
        }

        //generate the base url and display for default store id
        $baseUrl = Mage::helper('ddg')->generateDynamicUrl();

        //display the full url
        $text = sprintf(
            '%sconnector/email/review/code/%s/order_id/@%s@', $baseUrl,
            $passcode, $lastOrderId
        );
        $element->setData('value', $text);

        return parent::_getElementHtml($element);
    }

}