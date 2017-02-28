<?php

class Dotdigitalgroup_Email_Block_Adminhtml_System_Dynamic_Datafieldbutton
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    /**
     * @param $title
     * @return mixed
     */
    protected function _getAddRowButtonHtml($title)
    {
        return $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setType('button')
            ->setLabel($this->__($title))
            ->setOnClick("createDatafield(this.form, this);")
            ->toHtml();
    }

    /**
     * @param Varien_Data_Form_Element_Abstract $element
     * @return mixed
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $this->setElement($element);
        $originalData = $element->getOriginalData();

        return $this->_getAddRowButtonHtml(
            $this->__($originalData['button_label'])
        );
    }
}