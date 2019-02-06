<?php

require_once 'Mage/Adminhtml/controllers/UrlrewriteController.php';

class Dotdigitalgroup_Email_Adminhtml_UrlrewriteController extends Mage_Adminhtml_UrlrewriteController
{
    /**
     * Urlrewrite save action
     *
     */
    public function saveAction()
    {
        $productId  = $this->getRequest()->getParam('product', 0);
        if ($productId) {
            Mage::getResourceModel('ddg_automation/catalog')->setModified(array($productId));
        }
        parent::saveAction();
    }
}