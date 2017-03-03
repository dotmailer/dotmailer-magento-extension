<?php

class Dotdigitalgroup_Email_Adminhtml_Email_DashboardController
    extends Mage_Adminhtml_Controller_Action
{

    /**
     * Post dispatch.
     */
    public function postDispatch()
    {
        $currentWebsiteId = Mage::app()->getRequest()->getParam('website');

        //check the api valid for any of the website
        foreach (Mage::app()->getWebsites(true) as $website) {
            if ($currentWebsiteId == $website->getId()) {
                $passed = Mage::helper('ddg')->isEnabled($website);

                if (!$passed) {
                    $this->_redirect(
                        '*/system_config/edit',
                        array('section' => 'connector_api_credentials', 'website' => $website->getCode())
                    );
                }
            }
        }

    }

    /**
     * Main page.
     */
    public function indexAction()
    {
        $this->_title($this->__('Dashboard'));

        $this->loadLayout();
        $this->_setActiveMenu('email_connector');

        $this->renderLayout();
    }

    /**
     * Load Status Grid as ajax requst.
     */
    public function statusGridAction()
    {

        $block = $this->getLayout()->createBlock(
            'ddg_automation/adminhtml_dashboard_tabs_status'
        );
        $this->getResponse()->setBody($block->toHtml());
    }

    /**
     * Ajax tab for config data.
     */
    public function emailConfigAction()
    {
        $block = $this->getLayout()->createBlock(
            'ddg_automation/adminhtml_dashboard_tabs_config'
        );
        $this->getResponse()->setBody($block->toHtml());
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed(
            'email_connector/email_connector_dashboard'
        );
    }


    /**
     * Ajax save the state of expandbles fieldsets.
     */
    public function stateAction()
    {
        $configState = array(
            $this->getRequest()->getParam('container') => $this->getRequest()
                ->getParam('value')
        );
        $this->_saveState($configState);
    }

    /**
     * @param array $configState
     * @return bool
     */
    protected function _saveState($configState = array())
    {
        $adminUser = Mage::getSingleton('admin/session')->getUser();
        if (is_array($configState)) {
            $extra = $adminUser->getExtra();
            if (!is_array($extra)) {
                $extra = array();
            }

            if (!isset($extra['configState'])) {
                $extra['configState'] = array();
            }

            foreach ($configState as $fieldset => $state) {
                $extra['configState'][$fieldset] = $state;
            }

            $adminUser->saveExtra($extra);
        }

        return true;
    }

    /**
     * Ajax tab for view connector logs.
     */
    public function logsAction()
    {
        $block = $this->getLayout()->createBlock(
            'ddg_automation/adminhtml_dashboard_tabs_logs'
        );
        $this->getResponse()->setBody($block->toHtml());
    }
}