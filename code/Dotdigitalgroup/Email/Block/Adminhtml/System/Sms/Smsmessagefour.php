<?php

class Dotdigitalgroup_Email_Block_Adminhtml_System_Sms_Smsmessagefour extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    const DEFAULT_TEXT = 'Default SMS Text';

    /**
	 * SMS insert links.
	 * @param Varien_Data_Form_Element_Abstract $element
	 *
	 * @return string
	 */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract$element)
    {
        $element->setData('placeholder', self::DEFAULT_TEXT);
        $element->setData('after_element_html',
            "<a href='#' onclick=\"injectText('connector_sms_sms_four_message', '{{var order_number}}');return false;\">Insert Order Number</a>
            <a href='#' onclick=\"injectText('connector_sms_sms_four_message', '{{var customer_name}}');return false;\">Insert Customer Name</a>"
        );
        return parent::_getElementHtml($element);
    }


}