<?php

class Dotdigitalgroup_Email_Block_Adminhtml_System_Dynamic_Fieldset
    extends Mage_Adminhtml_Block_System_Config_Form_Fieldset
{
    /**
     * Render fieldset html
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $block = $this->getLayout()->createBlock('core/template', 'ddg_automation_dynamic_preview');
        $block->setTemplate('connector/preview.phtml');

        $this->setElement($element);
        $html = $this->_getHeaderHtml($element);
        $html .= $block->_toHtml();

        foreach ($element->getSortedElements() as $field) {
            $html .= $field->toHtml();
        }

        $html .= $this->_getFooterHtml($element);

        return $html;
    }
}