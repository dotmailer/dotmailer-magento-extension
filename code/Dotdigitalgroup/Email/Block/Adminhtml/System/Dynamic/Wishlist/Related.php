<?php

class Dotdigitalgroup_Email_Block_Adminhtml_System_Dynamic_Wishlist_Related
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    /**
     *
     * @param Varien_Data_Form_Element_Abstract $element
     *
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $passcode = Mage::helper('ddg')->getPasscodeWithWarning();
        $customerId = Mage::helper('ddg')->getMappedCustomerIdWithWarning();
        $baseUrl = Mage::helper('ddg')->generateDynamicUrl();

        //display the full url
        $text = sprintf(
            '%sconnector/wishlist/related/code/%s/customer_id/@%s@',
            $baseUrl,
            $passcode,
            $customerId
        );
        $element->setData('value', $text);

        return parent::_getElementHtml($element);
    }
}