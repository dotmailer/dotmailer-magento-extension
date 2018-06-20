<?php

class Dotdigitalgroup_Email_Adminhtml_TrialController
    extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        Mage::app()->getResponse()->setRedirect(Mage::helper('ddg/trial')->getIframeFormUrl());
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('system/config/connector_developer_settings');
    }
}