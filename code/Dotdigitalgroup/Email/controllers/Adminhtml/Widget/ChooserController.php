<?php

class  Dotdigitalgroup_Email_Adminhtml_Widget_ChooserController
    extends Mage_Adminhtml_Controller_Action
{

    /**
     * Ajax handler for product chooser.
     */
    public function productAction()
    {
        $block = $this->getLayout()->createBlock(
            'ddg_automation/adminhtml_widget_chooser_product',
            'email_connector_chooser_product',
            array('js_form_object' => $this->getRequest()->getParam('form'),
            )
        );

        if ($block) {
            $this->getResponse()->setBody($block->toHtml());
        }
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed(
            'system/config/connector_dynamic_content'
        );
    }
}
