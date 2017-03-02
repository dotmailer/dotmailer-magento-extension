<?php

class Dotdigitalgroup_Email_Model_Customer extends Mage_Customer_Model_Customer
{

    /**
     * Override - Send email with new account related information.
     *
     * @param string $type
     * @param string $backUrl
     * @param string $storeId
     * @param null $password
     * @return Mage_Customer_Model_Customer
     */
    public function sendNewAccountEmail($type = 'registered', $backUrl = '', $storeId = '0', $password = null)
    {

        if (Mage::getStoreConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_DISABLE_CUSTOMER_SUCCESS,
            $storeId
        )
        ) {
            return $this;
        }

        //password should be compatible with v1.9.2.4
        parent::sendNewAccountEmail($type, $backUrl, $storeId, $password);
    }
}