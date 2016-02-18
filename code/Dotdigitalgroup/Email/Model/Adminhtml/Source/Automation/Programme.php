<?php

class Dotdigitalgroup_Email_Model_Adminhtml_Source_Automation_Programme
{

	/**
	 * @return array
	 * @throws Mage_Core_Exception
	 */
	public function toOptionArray()
	{
		$fields = array();
		$websiteCode = Mage::app()->getRequest()->getParam('website', false);

		//website code param
        if (! $websiteCode)
	        $websiteCode = 0;//use admin

		$website = Mage::app()->getWebsite($websiteCode);

		$fields[] = array('value' => '0', 'label' => Mage::helper('ddg')->__('-- Disabled --'));

		if (Mage::helper('ddg')->isEnabled($website)) {

			$client = Mage::helper('ddg')->getWebsiteApiClient( $website );
			$programmes = $client->getPrograms();
			if ($programmes) {
				foreach ( $programmes as $one ) {
					if ( isset( $one->id ) ) {
						if ( $one->status == 'Active' ) {
							$fields[] = array(
								'value' => $one->id,
								'label' => Mage::helper( 'ddg' )->__( $one->name )
							);
						}
					}
				}
			}
		}

		return $fields;
	}

}