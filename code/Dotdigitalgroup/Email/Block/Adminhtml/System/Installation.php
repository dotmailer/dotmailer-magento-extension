<?php

class Dotdigitalgroup_Email_Block_Adminhtml_System_Installation
    extends Mage_Core_Block_Template
{
    /**
     * @var array
     */
    public $sections
        = array(
            'connector_api_credentials',
            'connector_data_mapping',
            'connector_sync_settings',
            'connector_roi_tracking',
            'connector_lost_baskets',
            'connector_reviews',
            'connector_dynamic_content',
            'connector_transactional_emails',
            'connector_configuration',
            'connector_developer_settings'
        );

    /**
     * get the website domain.
     *
     * @return string
     */
    public function getDomain()
    {
        return Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
    }

    /**
     * api username.
     *
     * @return string
     */
    public function getApiUsername()
    {
        return Mage::helper('ddg')->getApiUsername();
    }

    /**
     * check if the cron is running.
     *
     * @return bool
     */
    public function getCronInstalled()
    {
        return (Mage::helper('ddg')->getCronInstalled()) ? '1' : '0';
    }

    /*
     * Features enabled to use.
     *
     * @return array/null
     */
    public function getFeatures()
    {
        $section = $this->getRequest()->getParam('section');

        // not not track other sections
        if (!in_array($section, $this->sections)) {
            return null;
        }

        $features = array(
            'customer_sync'   => $this->getCustomerSync(),
            'guest_sync'      => $this->getGuestSync(),
            'subscriber_sync' => $this->getSubscriberSync(),
            'order_sync'      => $this->getOrderSync(),
            'catalog_sync'    => $this->getCatalogSync(),
            'dotmailer_smtp'  => $this->getDotmailerSmtp(),
            'roi'             => $this->getRoi()
        );

        return json_encode($features);
    }


    /**
     * @return bool
     */
    public function getCatalogSync()
    {
        return Mage::helper('ddg')->getCatalogSyncEnabled();
    }

    /**
     * @return bool
     */
    public function getOrderSync()
    {
        return Mage::helper('ddg')->getOrderSyncEnabled();
    }

    /**
     * @return bool
     */
    public function getSubscriberSync()
    {
        return Mage::helper('ddg')->isSubscriberSyncEnabled();
    }

    /**
     * @return bool
     */
    public function getGuestSync()
    {
        return Mage::helper('ddg')->getGuestSyncEnabled();
    }

    /**
     * @return bool
     */
    public function getCustomerSync()
    {
        return Mage::helper('ddg')->getContactSyncEnabled();
    }

    /**
     * @return bool
     */
    public function getRoi()
    {
        return Mage::helper('ddg')->getRoiTrackingEnabled();
    }

    /**
     * @return bool
     */
    public function getDotmailerSmtp()
    {
        return Mage::helper('ddg')->isSmtpEnabled();
    }

    /**
     * Magento version.
     *
     * @return string
     */
    public function getMageVersion()
    {
        return Mage::getVersion();
    }

    /**
     * Connector version.
     *
     * @return string
     */
    public function getConnectorVersion()
    {
        return Mage::helper('ddg')->getConnectorVersion();
    }

    /**
     * Get the api and website names.
     *
     * @return mixed|string
     */
    public function getWebsiteNames()
    {

        $data = Mage::helper('ddg')->getStringWebsiteApiAccounts();

        return $data;
    }

    /**
     * Get the account email.
     *
     * @return mixed
     */
    public function getAccountEmail()
    {
        return Mage::helper('ddg')->getAccountEmail();
    }


    /**
     * Use the beacon only on the api connector section.
     *
     * @return string
     */
    protected function _toHtml()
    {
        $section = $this->getAction()->getRequest()->getParam('section', false);

        if ($section == 'connector_api_credentials') {
            return parent::_toHtml();
        } else {
            return '';
        }
    }
}