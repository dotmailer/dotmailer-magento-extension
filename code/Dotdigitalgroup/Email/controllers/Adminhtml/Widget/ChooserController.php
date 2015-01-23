<?php

class  Dotdigitalgroup_Email_Adminhtml_Widget_ChooserController extends Mage_Adminhtml_Controller_Action
{
    /**
     * ajax handler for product chooser
     */
    public function productAction()
    {
        $block = $this->getLayout()->createBlock(
            'email_connector/adminhtml_widget_chooser_product', 'email_connector_chooser_product',
            array('js_form_object' => $this->getRequest()->getParam('form'),
        ));

        if ($block) {
            $this->getResponse()->setBody($block->toHtml());
        }
    }
}
