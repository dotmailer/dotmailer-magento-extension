<?php

class Dotdigitalgroup_Email_Block_Adminhtml_System_Dynamic_Couponinfo
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

        //full url
        $text = $baseUrl . 'connector/email/coupon/id/[INSERT ID HERE]/code/'
            . $passcode . '/expire_days/[INSERT NUMBER OF DAYS HERE]/@EMAIL@';

        $element->setData('value', $text);
        $element->setData('disabled', 'disabled');

        return parent::_getElementHtml($element);
    }

}