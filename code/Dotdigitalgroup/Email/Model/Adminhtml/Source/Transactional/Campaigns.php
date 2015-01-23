<?php

class Dotdigitalgroup_Email_Model_Adminhtml_Source_Transactional_Campaigns
{

	/**
	 * Option with campaign data.
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
            $website = 0;
	        $website = Mage::app()->getWebsite($website);
        }

	    //default option
	    $fields[] = array('value' => '0', 'label' => Mage::helper('connector')->__('-- Use system default --'));

	    if (!$website->getConfig(Dotdigitalgroup_Email_Helper_Transactional::XML_PATH_TRANSACTIONAL_API_ENABLED))
		    return $fields;

		    //set client credentials
        $client->setApiUsername(Mage::helper('connector/transactional')->getApiUsername($website))
            ->setApiPassword(Mage::helper('connector/transactional')->getApiPassword($website));

	    //campaigns from registry
        $savedCampaigns = Mage::registry('savedcampigns');

	    //current campaings from registry
        if ($savedCampaigns) {
            $campaigns = $savedCampaigns;
        } else {
	        //save into registry
            $campaigns = $client->getCampaigns();
            Mage::unregister('savedcampigns');
            Mage::register('savedcampigns', $campaigns);
        }

	    //add campign options
	    foreach ( $campaigns as $one ) {
		    if ( isset( $one->id ) )
			    $fields[] = array( 'value' => $one->id, 'label' => Mage::helper( 'connector' )->__( addslashes($one->name)) );
	    }

        return $fields;
    }
}