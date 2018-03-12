<?php

class Dotdigitalgroup_Email_Helper_Transactional extends Mage_Core_Helper_Abstract
{
    const XML_PATH_DDG_TRANSACTIONAL_ENABLED    = 'connector_transactional_emails/ddg_transactional/enabled';
    const XML_PATH_DDG_TRANSACTIONAL_HOST       = 'connector_transactional_emails/ddg_transactional/host';
    const XML_PATH_DDG_TRANSACTIONAL_USERNAME   = 'connector_transactional_emails/ddg_transactional/username';
    const XML_PATH_DDG_TRANSACTIONAL_PASSWORD   = 'connector_transactional_emails/ddg_transactional/password';
    const XML_PATH_DDG_TRANSACTIONAL_PORT       = 'connector_transactional_emails/ddg_transactional/port';
    const XML_PATH_DDG_TRANSACTIONAL_DEBUG      = 'connector_transactional_emails/ddg_transactional/debug_mode';

    /**
     * Transactional Email enabled.
     *
     * @param null $storeId
     * @return bool
     */
    public function isEnabled($storeId = null)
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_DDG_TRANSACTIONAL_ENABLED, $storeId);
    }

    /**
     * @param null $storeId
     * @return mixed
     */
    public function getSmtpHost($storeId = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_DDG_TRANSACTIONAL_HOST, $storeId);
    }

    /**
     * @param null $storeId
     * @return mixed
     */
    public function getSmtpUsername($storeId = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_DDG_TRANSACTIONAL_USERNAME, $storeId);
    }

    /**
     * @param null $storeId
     * @return mixed
     */
    public function getSmtpPassword($storeId = null)
    {
        $pass = Mage::getStoreConfig(self::XML_PATH_DDG_TRANSACTIONAL_PASSWORD, $storeId);

        return Mage::helper('core')->decrypt($pass);
    }

    /**
     * @param null $storeId
     * @return mixed
     */
    public function getSmtpPort($storeId = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_DDG_TRANSACTIONAL_PORT, $storeId);
    }

    /**
     * @return bool
     */
    public function isDebugEnabled($storeId = null)
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_DDG_TRANSACTIONAL_DEBUG, $storeId);
    }

    /**
     * @param null $storeId
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

        if ($this->isDebugEnabled($storeId)) {
            $configToLog = $config;
            unset($configToLog['password']);
            Mage::log('Mail transport config : ' . implode(',', $configToLog));
        }

        $transport = new Zend_Mail_Transport_Smtp(
            $this->getSmtpHost($storeId),
            $config
        );

        return $transport;
    }

    /**
     * @param $templateText
     * @return string
     */
    public function decompresString($templateText)
    {
        return gzuncompress(base64_decode($templateText));
    }

    /**
     * @param $templateText
     * @return string
     */
    public function compresString($templateText)
    {
        return base64_encode(gzcompress($templateText, 9));
    }

    /**
     * @param $string
     * @return bool
     */
    public function isStringCompressed($string)
    {
        //check if the data is compressed
        if (substr($string, 0, 1) == 'e' && substr_count($string, ' ') == 0) {
            return true;
        }

        return false;
    }

    /**
     * Check if the template code is containing dotmailer.
     *
     * @param $templateCode
     * @return bool
     */
    public function isDotmailerTemplate($templateCode)
    {
        preg_match("/\_\d{1,10}$/", $templateCode, $matches);

        if (count($matches)) {
            return true;
        }

        return false;
    }
}
