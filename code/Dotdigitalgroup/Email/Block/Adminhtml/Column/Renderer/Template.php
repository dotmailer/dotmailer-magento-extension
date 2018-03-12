<?php

class Dotdigitalgroup_Email_Block_Adminhtml_Column_Renderer_Template
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{

    /**
     * Render grid columns.
     *
     * @param Varien_Object $row
     *
     * @return string
     */
    public function render(Varien_Object $row)
    {
        if (Mage::helper('ddg/transactional')->isDotmailerTemplate($row->getTemplateCode())) {

            return utf8_decode($row->getTemplateSubject());
        }

        return Mage::helper('adminhtml')->__($row->getTemplateSubject());
    }
}
