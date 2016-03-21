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
		$email              = $subscriber->getEmail();
		$storeId            = $subscriber->getStoreId();
		$subscriberStatus   = $subscriber->getSubscriberStatus();
		$websiteId = Mage::app()->getStore($subscriber->getStoreId())->getWebsiteId();

		//check if enabled
		if ( ! Mage::helper('ddg')->isEnabled($websiteId))
			return $this;
		try{

			$contactEmail = Mage::getModel('ddg_automation/contact')->loadByCustomerEmail($email, $websiteId);

			// only for subsribers
			if ($subscriberStatus == Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED) {
				//set contact as subscribed
				$contactEmail->setSubscriberStatus( $subscriberStatus )
					->setIsSubscriber('1');

				Mage::getModel('ddg_automation/importer')->registerQueue(
					Dotdigitalgroup_Email_Model_Importer::IMPORT_TYPE_SUBSCRIBER_RESUBSCRIBED,
					array('email' => $email),
					Dotdigitalgroup_Email_Model_Importer::MODE_SUBSCRIBER_RESUBSCRIBED,
					$websiteId
				);
				// reset the subscriber as suppressed
				$contactEmail->setSuppressed(null);

				//not subscribed
			}

			// fix for a multiple hit of the observer
			$emailReg =  Mage::registry($email . '_subscriber_save');
			if ($emailReg){
				return $this;
			}
			Mage::register($email . '_subscriber_save', $email);

			//add subscriber to automation
			$this->_addSubscriberToAutomation($email, $subscriber, $websiteId);

			//update the contact
			$contactEmail->setStoreId($storeId);

			//update contact
			$contactEmail->save();

		}catch(Exception $e){
			Mage::logException($e);
			Mage::helper('ddg')->getRaygunClient()->SendException($e, array(Mage::getBaseUrl('web')));
		}
		return $this;
	}

	protected function _addSubscriberToAutomation($email, $subscriber, $websiteId){

		$storeId            = $subscriber->getStoreId();
		$store = Mage::app()->getStore($storeId);
		$programId     = Mage::helper( 'ddg' )->getAutomationIdByType( 'XML_PATH_CONNECTOR_AUTOMATION_STUDIO_SUBSCRIBER', $websiteId );
		//not mapped ignore
		if (! $programId)
			return;
		try {
			//check the subscriber alredy exists
			$enrolmentCollection = Mage::getModel('ddg_automation/automation')->getCollection()
				->addFieldToFilter('email', $email)
				->addFieldToFilter('automation_type', Dotdigitalgroup_Email_Model_Automation::AUTOMATION_TYPE_NEW_SUBSCRIBER)
				->addFieldToFilter('website_id', $websiteId);
			$enrolmentCollection->getSelect()->limit(1);
			$enrolment = $enrolmentCollection->getFirstItem();

			//add new subscriber to automation
			if (! $enrolment->getId()) {
				//save subscriber to the queue
				$automation = Mage::getModel( 'ddg_automation/automation' );
				$automation->setEmail( $email )
					->setAutomationType( Dotdigitalgroup_Email_Model_Automation::AUTOMATION_TYPE_NEW_SUBSCRIBER )
					->setEnrolmentStatus( Dotdigitalgroup_Email_Model_Automation::AUTOMATION_STATUS_PENDING )
					->setTypeId( $subscriber->getId() )
					->setWebsiteId( $websiteId )
					->setStoreName( $store->getName() )
					->setProgramId( $programId );
				$automation->save();
			}
		}catch(Exception $e){
			Mage::logException($e);
		}
	}

}