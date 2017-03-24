<?php

class Dotdigitalgroup_Email_Block_Adminhtml_System_Advanced_Reimportorders
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element
    ) 
    {
        $this->setElement($element);

        return $this->_getAddRowButtonHtml($this->__("Run Now"));
    }

    protected function _getAddRowButtonHtml($title)
    {
        $url = $this->getUrl(
            "*/connector/resetorders",
            array(
                '_query' => array(
                    'from' => '',
                    'to' => '',
                    'tp' => ''
                )
            )
        );

        return $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setType('button')
            ->setLabel($this->__($title))
            ->setId($this->getElement()->getId())
            ->setOnClick("window.location.href='" . $url . "'")
            ->toHtml();
    }

}
