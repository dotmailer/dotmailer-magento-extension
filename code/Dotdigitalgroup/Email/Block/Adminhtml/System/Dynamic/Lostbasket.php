<?php

class Dotdigitalgroup_Email_Block_Adminhtml_System_Dynamic_Lostbasket extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    /** label */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
	    //base url for dynamic content
        $baseUrl = Mage::helper('connector')->generateDynamicUrl();
        $code = Mage::helper('connector')->getPasscode();

	    //config passcode
	    if(!strlen($code))
            $code = '[PLEASE SET UP A PASSCODE]';
	    // full url
        $text =  $baseUrl  . 'connector/email/basket/email/@EMAIL@/code/'. $code;

        $element->setData('value', $text);
        return parent::_getElementHtml($element);
    }

}