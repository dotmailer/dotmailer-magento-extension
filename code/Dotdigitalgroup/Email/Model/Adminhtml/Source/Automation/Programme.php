<?php

class Dotdigitalgroup_Email_Model_Adminhtml_Source_Automation_Programme
{

	public function toOptionArray()
	{
		$fields = array();
		$websiteName = Mage::app()->getRequest()->getParam('website', false);
		//admin
		$website = 0;
		$fields[] = array('value' => '0', 'label' => Mage::helper('ddg')->__('-- Disabled --'));
		if ($websiteName) {
			$website = Mage::app()->getWebsite($websiteName);
		}

		if (Mage::helper('ddg')->isEnabled($website)) {

			$client = Mage::helper( 'ddg' )->getWebsiteApiClient( $website );
			$programmes = $client->getPrograms();

			foreach ( $programmes as $one ) {
				if ( isset( $one->id ) ) {
					$fields[] = array( 'value' => $one->id, 'label' => Mage::helper( 'ddg' )->__( $one->name ) );
				}
			}
		}

		return $fields;
	}

}