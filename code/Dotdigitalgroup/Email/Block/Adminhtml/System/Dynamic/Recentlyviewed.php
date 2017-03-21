<?php

class Dotdigitalgroup_Email_Block_Adminhtml_System_Dynamic_Recentlyviewed
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
        $customerId = Mage::helper('ddg')->getMappedCustomerIdWithWarning();

        //dynamic content url
        $text = sprintf(
            '%sconnector/report/recentlyviewed/code/%s/customer_id/@%s@',
            $baseUrl,
            $passcode,
            $customerId
        );
        $element->setData('value', $text);

        return parent::_getElementHtml($element);

    }
}