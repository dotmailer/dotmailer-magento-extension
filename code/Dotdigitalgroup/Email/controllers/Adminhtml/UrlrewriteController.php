<?php

/**
 * Urlrewrite save actions
 *
 * Enterprise behaves slightly differently and needs to extend an Enterprise parent class.
 *
 */

if (Mage::helper('ddg')->isEnterprise() && version_compare(Mage::getVersion(), '1.12.0.13', '>=')) {

    require_once 'Enterprise/UrlRewrite/controllers/Adminhtml/UrlrewriteController.php';

    class Dotdigitalgroup_Email_Adminhtml_UrlRewriteHandler extends Enterprise_UrlRewrite_Adminhtml_UrlrewriteController
    {}

} else {

    require_once 'Mage/Adminhtml/controllers/UrlrewriteController.php';

    class Dotdigitalgroup_Email_Adminhtml_UrlRewriteHandler extends Mage_Adminhtml_UrlrewriteController
    {}
}

class Dotdigitalgroup_Email_Adminhtml_UrlrewriteController extends Dotdigitalgroup_Email_Adminhtml_UrlRewriteHandler
{
    public function saveAction()
    {
        $productId  = $this->getRequest()->getParam('product', 0);
        if ($productId) {
            Mage::getResourceModel('ddg_automation/catalog')->setUnProcessed(array($productId));
        }
        parent::saveAction();
    }
}