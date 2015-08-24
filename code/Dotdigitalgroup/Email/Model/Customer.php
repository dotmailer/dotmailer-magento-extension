<?php

class Dotdigitalgroup_Email_Model_Customer extends Mage_Customer_Model_Customer
{
    /**
     * overwrites the default function
     *
     * @param string $type
     * @param string $backUrl
     * @param string $storeId
     * @return Mage_Customer_Model_Customer|void
     */
    public function sendNewAccountEmail($type = 'registered', $backUrl = '', $storeId = '0')
    {
        if(Mage::getStoreConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_DISABLE_CUSTOMER_SUCCESS, $storeId))
            return;

        parent::sendNewAccountEmail($type, $backUrl, $storeId);
    }
}