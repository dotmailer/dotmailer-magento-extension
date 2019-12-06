<?php

/**
 * Renderer for sub-heading in fieldset
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Dotdigitalgroup_Email_Block_Adminhtml_System_Dynamic_Subheadingwithcomment
    extends Mage_Adminhtml_Block_Abstract implements Varien_Data_Form_Element_Renderer_Interface
{
    /**
     * Render element html
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        return sprintf(
            '<tr class="system-fieldset-sub-head" id="row_%s">
                <td colspan="5">
                    <h4 id="%s">%s</h4>
                    <p class="note"><span>%s</span></p>
                </td>
            </tr>',
            $element->getHtmlId(),
            $element->getHtmlId(),
            $element->getLabel(),
            $element->getComment()
        );
    }
}
