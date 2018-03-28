<?php

class Dotdigitalgroup_Email_Block_Adminhtml_System_Advanced_Runtemplatesync
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    /**
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $this->setElement($element);

        return $this->_getAddRowButtonHtml($this->__("Run Now"));
    }

    /**
     * @param $title
     * @return mixed
     */
    protected function _getAddRowButtonHtml($title)
    {
        $url = Mage::helper('adminhtml')->getUrl("*/connector/runtemplatesync");

        return $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setType('button')
            ->setLabel($this->__($title))
            ->setOnClick("window.location.href='" . $url . "'")
            ->toHtml();
    }
}
