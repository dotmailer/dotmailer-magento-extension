<?php

class Dotdigitalgroup_Email_Model_Adminhtml_Source_Campaigns
{

	/**
	 * Returns the campaigns options.
	 *
	 * @return array
	 * @throws Mage_Core_Exception
	 */
	public function toOptionArray()
    {
        $fields = array();
        $websiteName = Mage::app()->getRequest()->getParam('website', false);
        //admin
        $website = 0;
	    $fields[] = array('value' => '0', 'label' => Mage::helper('connector')->__('-- Please Select --'));

	    if ($websiteName) {
            $website = Mage::app()->getWebsite($websiteName);
        }

	    $enabled = Mage::helper('connector')->isEnabled($website);

	    //api enabled get campaigns
	    if ($enabled) {
		    $client = Mage::helper( 'connector' )->getWebsiteApiClient( $website );

		    $savedCampaigns = Mage::registry( 'savedcampigns' );

			//get campaigns from registry
		    if ( $savedCampaigns ) {
			    $campaigns = $savedCampaigns;
		    } else {
			    $campaigns = $client->getCampaigns();
			    Mage::unregister( 'savedcampigns' );
			    Mage::register( 'savedcampigns', $campaigns );
		    }

		    foreach ( $campaigns as $one ) {
			    if ( isset( $one->id ) ) {
				    $fields[] = array(
					    'value' => $one->id,
					    'label' => Mage::helper( 'connector' )->__( addslashes( $one->name ) )
				    );
			    }
		    }
	    }

        return $fields;
    }

}