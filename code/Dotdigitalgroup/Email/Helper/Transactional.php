<?php

class Dotdigitalgroup_Email_Helper_Transactional
    extends Mage_Core_Helper_Abstract
{

    const XML_PATH_DDG_TRANSACTIONAL_ENABLED = 'connector_transactional_emails/ddg_transactional/enabled';
    const XML_PATH_DDG_TRANSACTIONAL_HOST = 'connector_transactional_emails/ddg_transactional/host';
    const XML_PATH_DDG_TRANSACTIONAL_USERNAME = 'connector_transactional_emails/ddg_transactional/username';
    const XML_PATH_DDG_TRANSACTIONAL_PASSWORD = 'connector_transactional_emails/ddg_transactional/password';
    const XML_PATH_DDG_TRANSACTIONAL_PORT = 'connector_transactional_emails/ddg_transactional/port';
    const XML_PATH_DDG_TRANSACTIONAL_DEBUG = 'connector_transactional_emails/ddg_transactional/debug_mode';


    /**
     * Transactional Email enabled.
     * @return bool
     */
    public function isEnabled($storeId = null)
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_DDG_TRANSACTIONAL_ENABLED, $storeId);
    }

    /**
     * @return mixed
     */
    public function getSmtpHost($storeId = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_DDG_TRANSACTIONAL_HOST, $storeId);
    }

    /**
     * @return mixed
     */
    public function getSmtpUsername($storeId = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_DDG_TRANSACTIONAL_USERNAME, $storeId);
    }

    /**
     * @return mixed
     */
    public function getSmtpPassword($storeId = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_DDG_TRANSACTIONAL_PASSWORD, $storeId);
    }

    /**
     * @return mixed
     */
    public function getSmtpPort($storeId = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_DDG_TRANSACTIONAL_PORT, $storeId);
    }

    /**
     *
     * @return bool
     */
    public function isDebugEnabled($storeId = null)
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_DDG_TRANSACTIONAL_DEBUG, $storeId);
    }

    /**
     * @param $storeId int
     * @return Zend_Mail_Transport_Smtp
     */
    public function getTransport($storeId = null)
    {
        $config = array(
            'port' => $this->getSmtpPort($storeId),
            'auth' => 'login',
            'username' => $this->getSmtpUsername($storeId),
            'password' => $this->getSmtpPassword($storeId),
            'ssl' => 'tls'
        );

        if ($this->isDebugEnabled()) {
            $configToLog = $config;
            unset($configToLog['password']);
            Mage::log('Mail transport config : ' . implode(',', $configToLog));
        }

        $transport = new Zend_Mail_Transport_Smtp(
            $this->getSmtpHost($storeId), $config
        );

        return $transport;
    }
}