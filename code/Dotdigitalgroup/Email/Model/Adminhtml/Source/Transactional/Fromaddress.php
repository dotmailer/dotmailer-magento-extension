<?php

class Dotdigitalgroup_Email_Model_Adminhtml_Source_Transactional_Fromaddress
{

	/**
	 * Returns all custom from addresses.
	 *
	 * @return array
	 * @throws Mage_Core_Exception
	 */
	public function toOptionArray()
    {
        $fields = array();
        $client = Mage::getModel('email_connector/apiconnector_client');

	    //website param
        $websiteName = Mage::app()->getRequest()->getParam('website', false);
        if ($websiteName) {
            $website = Mage::app()->getWebsite($websiteName);
        } else {
	        //not set use default
            $website = 0;
	        $website = Mage::app()->getWebsite($website);
        }

	    //default option
	    $fields[] = array('value' => '0', 'label' => Mage::helper('connector')->__('-- Please select --'));

	    //transactional disabled return defualt option
	    if (! $website->getConfig(Dotdigitalgroup_Email_Helper_Transactional::XML_PATH_TRANSACTIONAL_API_ENABLED))
		    return $fields;

	    //set api credentials
        $client->setApiUsername(Mage::helper('connector/transactional')->getApiUsername($website))
            ->setApiPassword(Mage::helper('connector/transactional')->getApiPassword($website));

        $savedFromAddressList = Mage::registry('savedFromAddressList');
	    //load from regirstry
	    if ($savedFromAddressList) {
		    $fromAddressList = $savedFromAddressList;
	    } else {
		    // retrive the transactionals
		    $fromAddressList = $client->getCustomFromAddresses();
		    Mage::unregister('savedFromAddressList');
		    Mage::register('savedFromAddressList', $fromAddressList);
	    }

	    //add all options
	    foreach ($fromAddressList as $one) {
		    if(isset($one->id))
			    $fields[] = array('value' => $one->id, 'label' => Mage::helper('connector')->__($one->email));
	    }

        return $fields;
    }
}