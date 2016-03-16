<?php

class Dotdigitalgroup_Email_Helper_Transactional
    extends Mage_Core_Helper_Abstract
{

    const XML_PATH_DDG_TRANSACTIONAL_ENABLED = 'connector_transactional_emails/ddg_transactional/enabled';
    const XML_PATH_DDG_TRANSACTIONAL_HOST = 'connector_transactional_emails/ddg_transactional/host';
    const XML_PATH_DDG_TRANSACTIONAL_USERNAME = 'connector_transactional_emails/ddg_transactional/username';
    const XML_PATH_DDG_TRANSACTIONAL_PASSWORD = 'connector_transactional_emails/ddg_transactional/password';
    const XML_PATH_DDG_TRANSACTIONAL_PORT = 'connector_transactional_emails/ddg_transactional/port';
    const XML_PATH_DDG_TRANSACTIONAL_DEBUG = 'connector_transactional_emails/ddg_transactional/debug';


    public function isEnabled()
    {
        return Mage::getStoreConfigFlag(
            self::XML_PATH_DDG_TRANSACTIONAL_ENABLED
        );

    }

    public function getSmtpHost()
    {

        return Mage::getStoreConfig(self::XML_PATH_DDG_TRANSACTIONAL_HOST);

    }

    public function getSmtpUsername()
    {
        return Mage::getStoreConfig(self::XML_PATH_DDG_TRANSACTIONAL_USERNAME);

    }

    public function getSmtpPassword()
    {
        return Mage::getStoreConfig(self::XML_PATH_DDG_TRANSACTIONAL_PASSWORD);

    }

    public function getSmtpPort()
    {
        return Mage::getStoreConfig(self::XML_PATH_DDG_TRANSACTIONAL_PORT);

    }

    public function isDebugEnabled()
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_DDG_TRANSACTIONAL_DEBUG);
    }


    public function getTransport()
    {
        $config = array(
            'port' => $this->getSmtpPort(),
            'auth' => 'login',
            'username' => $this->getSmtpUsername(),
            'password' => $this->getSmtpPassword(),
            'ssl' => 'tls'
        );

        if ($this->isDebugEnabled()) {
            Mage::log('Mail transport config : ' . implode(',', $config));
        }

        $transport = new Zend_Mail_Transport_Smtp(
            $this->getSmtpHost(), $config
        );

        return $transport;
    }
}