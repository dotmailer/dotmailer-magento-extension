<?php

class Dotdigitalgroup_Email_Model_Adminhtml_Source_Sync_Yesno
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        //get current scope website code, set the code to admin if empty
        $websiteCode = (Mage::getSingleton('adminhtml/config_data')->getWebsite())? Mage::getSingleton('adminhtml/config_data')->getWebsite() : 'admin';

        //for current scope website check if entry exist in registry. if not than
        //than get account data and store it in registry to re-use
        if (! Mage::registry('ddg-account-info-'.$websiteCode)) {
            //websites with code as key
			$websites = Mage::app()->getWebsites(true, true);
			$website = $websites[$websiteCode];

            //if scope is empty or no id than load default
            if (empty($website) or !$website->getId())
                $website = 0;

            $apiUsername = Mage::helper('ddg')->getApiUsername($website);
            $apiPassword = Mage::helper('ddg')->getApiPassword($website);
            $data = Mage::getModel('ddg_automation/apiconnector_client')
                ->setApiUsername($apiUsername)
                ->setApiPassword($apiPassword)
                ->getAccountInfo();

            //save entry in registry for current website scope
            Mage::register('ddg-account-info-'.$websiteCode, $data);
        }

        //get from registry
        $data = Mage::registry('ddg-account-info-'.$websiteCode);
        //if properties property exist
        if (isset($data->properties)) {
            $propertyNames = array();
            //loop all and save property names
            foreach ($data->properties as $one) {
                $propertyNames[] = $one->name;
            }

            //only return Yes/No option if data allowance properties exist
            if(in_array('TransactionalDataAllowanceInMegabytes', $propertyNames) &&
                in_array('TransactionalDataUsageInMegabytes', $propertyNames)){
                return array(
                    array('value' => 1, 'label'=>Mage::helper('adminhtml')->__('Yes')),
                    array('value' => 0, 'label'=>Mage::helper('adminhtml')->__('No')),
                );
            }
        }

        //return default message if above is scenarios are failed.
        return array(
            array('value' => 0, 'label'=>Mage::helper('adminhtml')->__('Not enabled on account'))
        );
    }
}