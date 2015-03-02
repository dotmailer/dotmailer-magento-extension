<?php

class Dotdigitalgroup_Email_Model_Newsletter_Observer
{

	/**
	 * Change the subscribsion for an contact.
	 * Add new subscribers to an automation.
	 *
	 * @param Varien_Event_Observer $observer
	 *
	 * @return $this
	 */
	public function handleNewsletterSubscriberSave(Varien_Event_Observer $observer)
    {
	    $helper = Mage::helper('ddg');
	    $subscriber = $observer->getEvent()->getSubscriber();
	    $storeId            = $subscriber->getStoreId();
	    $email              = $subscriber->getEmail();
	    $subscriberStatus   = $subscriber->getSubscriberStatus();
		$store = Mage::app()->getStore($storeId);
		$website = $store->getWebsite();

        $websiteId = Mage::app()->getStore($subscriber->getStoreId())->getWebsiteId();
        $contactEmail = Mage::getModel('ddg_automation/contact')->loadByCustomerEmail($email, $websiteId);
	    try{
	        // send new subscriber to an automation
	        if (! Mage::getModel('newsletter/subscriber')->loadByEmail($email)->getId()) {
				//data fields
				Mage::helper('ddg')->updateDataFields($email, $website, $store->getName());
		        $this->_postSubscriberToAutomation($email, $websiteId);
	        }

            // only for subsribers
            if ($subscriberStatus == Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED) {

	            $client = Mage::helper('ddg')->getWebsiteApiClient($websiteId);

	            //check for website client
	            if ($client) {
		            //set contact as subscribed
		            $contactEmail->setSubscriberStatus( $subscriberStatus )
		                         ->setIsSubscriber('1');

		            $apiContact = $client->postContacts( $email );

		            //resubscribe suppressed contacts
		            if ( isset( $apiContact->status ) && $apiContact->status == 'Suppressed' ) {
			            $client->postContactsResubscribe( $apiContact );
		            }
	            }
	            // reset the subscriber as suppressed
                $contactEmail->setSuppressed(null);

	        //not subscribed
            } else {
	            //skip if contact is suppressed
	            if ($contactEmail->getSuppressed())
		            return $this;
                //update contact id for the subscriber
                $client = Mage::helper('ddg')->getWebsiteApiClient($websiteId);
	            //check for website client
	            if ($client) {
		            $contactId = $contactEmail->getContactId();
		            //get the contact id
		            if ( !$contactId ) {
			            //if contact id is not set get the contact_id
			            $result = $client->postContacts( $email );
			            if ( isset( $result->id ) ) {
				            $contactId = $result->id;
			            } else {
				            //no contact id skip
				            $contactEmail->setSuppressed( '1' )
				                         ->save();
				            return $this;
			            }
		            }
		            //remove contact from address book
		            $client->deleteAddressBookContact( $helper->getSubscriberAddressBook( $websiteId ), $contactId );
	            }
                $contactEmail->setIsSubscriber(null)
	                ->setSubscriberStatus(Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED);
            }

	        //update the contact
            $contactEmail->setStoreId($storeId);
	        if (isset($contactId))
		        $contactEmail->setContactId($contactId);
	        //update contact
	        $contactEmail->save();

        }catch(Exception $e){
            Mage::logException($e);
	        Mage::helper('ddg')->getRaygunClient()->SendException($e, array(Mage::getBaseUrl('web')));
        }
        return $this;
    }


	private function _postSubscriberToAutomation( $email, $websiteId ) {
		/**
		 * Automation Programm
		 */
		$subscriberAutoCamaignId = Mage::helper('ddg')->getWebsiteConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_AUTOMATION_STUDIO_SUBSCRIBER, $websiteId);
		$enabled = Mage::helper('ddg')->getWebsiteConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_API_ENABLED, $websiteId);
		//enabled and mapped
		if ($enabled && $subscriberAutoCamaignId) {
            Mage::helper( 'ddg' )->log( 'AS - subscriber automation Campaign id : ' . $subscriberAutoCamaignId );
			$client = Mage::helper( 'ddg' )->getWebsiteApiClient( $websiteId );
			//create new contact
			$apiContact = $client->postContacts($email);

			// get a program by id
			$program = $client->getProgramById($subscriberAutoCamaignId);
			/**
			 * id
			 * name
			 * status
			 * dateCreated
			 */
            Mage::helper( 'ddg' )->log( 'AS - get subscriber Program id : ' . $program->id);
			//check for active program with status "Active"
			if (isset($program->status) && $program->status == 'Active') {

				$data = array(
					'Contacts'     => array( $apiContact->id ),
					'ProgramId'    => $program->id,
					'Status'       => $program->status,
					'DateCreated'  => $program->dateCreated,
					'AddressBooks' => array()
				);
				//add contact to automation enrolment
				$client->postProgramsEnrolments( $data );
			}
		}
	}
}