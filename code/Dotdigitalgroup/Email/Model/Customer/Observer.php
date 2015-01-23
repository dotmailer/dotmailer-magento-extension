<?php

class Dotdigitalgroup_Email_Model_Customer_Observer
{
    /**
     * Create new contact or update info, also check for email change
     * event: customer_save_before
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function handleCustomerSaveBefore(Varien_Event_Observer $observer)
    {
        $customer = $observer->getEvent()->getCustomer();
        $email      = $customer->getEmail();
        $websiteId  = $customer->getWebsiteId();
        $customerId = $customer->getEntityId();
        $isSubscribed = $customer->getIsSubscribed();
        try{
            $emailBefore = Mage::getModel('customer/customer')->load($customer->getId())->getEmail();
            $contactModel = Mage::getModel('email_connector/contact')->loadByCustomerEmail($emailBefore, $websiteId);
            //email change detection
            if ($email != $emailBefore) {
                Mage::helper('connector')->log('email change detected : '  . $email . ', after : ' . $emailBefore .  ', website id : ' . $websiteId);
                $enabled = Mage::helper('connector')->getWebsiteConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_API_ENABLED, $websiteId);

                if ($enabled) {
                    $client = Mage::helper('connector')->getWebsiteApiClient($websiteId);
                    $subscribersAddressBook = Mage::helper('connector')->getWebsiteConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SUBSCRIBERS_ADDRESS_BOOK_ID, $websiteId);
                    $response = $client->postContacts($emailBefore);
                    //check for matching email
                    if (isset($response->id)) {
                        if ($email != $response->email) {
                            $data = array(
                                'Email' => $email,
                                'EmailType' => 'Html'
                            );
                            //update the contact with same id - different email
                            $client->updateContact($response->id, $data);

                        }
                        if (!$isSubscribed && $response->status == 'Subscribed') {
                            $client->deleteAddressBookContact($subscribersAddressBook, $response->id);
                        }
                    } elseif (isset($response->message)) {
                        Mage::helper('connector')->log('Email change error : ' . $response->message);
                    }
                }
                $contactModel->setEmail($email);
            }

	        $contactModel->setEmailImported(Dotdigitalgroup_Email_Model_Contact::EMAIL_CONTACT_NOT_IMPORTED)
                ->setCustomerId($customerId)
                ->save();
        }catch(Exception $e){
            Mage::logException($e);
        }

        return $this;
    }

	/**
	 * Add new customers to the automation.
	 * @param Varien_Event_Observer $observer
	 *
	 * @return $this
	 */
	public function handleCustomerRegiterSuccess(Varien_Event_Observer $observer)
	{
		$customer = $observer->getEvent()->getCustomer();
		$email      = $customer->getEmail();
		$websiteId  = $customer->getWebsiteId();

		// send customer to automation mapped
        $automationType = 'XML_PATH_CONNECTOR_AUTOMATION_STUDIO_CUSTOMER';
		$this->_postCustomerToAutomation($email, $websiteId, $automationType);

		return $this;
	}

	/**
	 * Remove the contact on customer delete.
	 *
	 * @param Varien_Event_Observer $observer
	 *
	 * @return $this
	 */
	public function handleCustomerDeleteAfter(Varien_Event_Observer $observer)
    {
        $customer = $observer->getEvent()->getCustomer();
        $email      = $customer->getEmail();
        $websiteId  = $customer->getWebsiteId();
        /**
         * Remove contact.
         */
        try{
            $contactModel = Mage::getModel('email_connector/contact')->loadByCustomerEmail($email, $websiteId);
            if ($contactModel->getId()) {
                //remove contact
                $contactModel->delete();
            }
            //remove from account
            $client = Mage::helper('connector')->getWebsiteApiClient($websiteId);
            $apiContact = $client->postContacts($email);
            if(! isset($apiContact->message) && isset($apiContact->id))
                $client->deleteContact($apiContact->id);

        }catch (Exception $e){
            Mage::logException($e);
        }
        return $this;
    }

    /**
     * enrol single contact to automation
     *
     * @param $email
     * @param $websiteId
     * @param $automationType
     */
	private function _postCustomerToAutomation( $email, $websiteId, $automationType) {
		/**
		 * Automation Programme
		 */
        $path = constant('Dotdigitalgroup_Email_Helper_Config::' . $automationType);
		$automationCampaignId = Mage::helper('connector')->getWebsiteConfig($path, $websiteId);
		$enabled = Mage::helper('connector')->getWebsiteConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_API_ENABLED, $websiteId);

		//add customer to automation
		if ($enabled && $automationCampaignId) {
			Mage::helper( 'connector' )->log( 'AS - ' . $automationType . ' automation Campaign id : ' . $automationCampaignId );
			$client = Mage::helper( 'connector' )->getWebsiteApiClient( $websiteId );
			$apiContact = $client->postContacts($email);

			// get a program by id
			$program = $client->getProgramById($automationCampaignId);
			/**
			 * id
			 * name
			 * status
			 * dateCreated
			 */
			Mage::helper( 'connector' )->log( 'AS - get ' . $automationType . ' Program id : ' . $program->id);
            //check for active program with status "Active"
            if (isset($program->status) && $program->status == 'Active') {
                $data = array(
                    'Contacts' => array($apiContact->id),
                    'ProgramId'   => $program->id,
                    'Status'      => $program->status,
                    'DateCreated' => $program->dateCreated,
                    'AddressBooks' => array()
                );
                $client->postProgramsEnrolments($data);
            }
		}
	}

    /**
     * Set contact to re-import if registered customer submitted a review. Save review in email_review table.
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function reviewSaveAfter(Varien_Event_Observer $observer)
    {
        $dataObject = $observer->getEvent()->getDataObject();
        if($dataObject->getCustomerId() && $dataObject->getStatusId() == '1'){
            $customerId = $dataObject->getCustomerId();
            $helper = Mage::helper('connector');
            $helper->setConnectorContactToReImport($customerId);
            //save review info in the table
            $this->_registerReview($dataObject);
            // send customer to automation mapped
            $automationType = 'XML_PATH_CONNECTOR_AUTOMATION_STUDIO_REVIEW';
            $customer = Mage::getModel('customer/customer')->load($customerId);
            $email      = $customer->getEmail();
            $websiteId  = $customer->getWebsiteId();
            $this->_postCustomerToAutomation($email, $websiteId, $automationType);
        }
        return $this;
    }

    /**
     * register review
     *
     * @param $review
     */
    private function _registerReview($review)
    {
        try{
            $emailReview = Mage::getModel('email_connector/review');
            $emailReview->setReviewId($review->getReviewId())
                ->setCustomerId($review->getCustomerId())
                ->setStoreId($review->getStoreId())
                ->save();
        }catch(Exception $e){
            Mage::logException($e);
        }
    }

    /**
     * wishlist save after observer. save new wishlist in the email_wishlist table.
     *
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function wishlistSaveAfter(Varien_Event_Observer $observer)
    {
        if($observer->getEvent()->getObject() instanceof Mage_Wishlist_Model_Wishlist) {
            $wishlist = $observer->getEvent()->getObject()->getData();
            if (is_array($wishlist) && isset($wishlist['customer_id'])) {
                //save wishlist info in the table
                $this->_registerWishlist( $wishlist );
            }
        }
    }

    /**
     * register wishlist
     *
     * @param $wishlist
     */
    private function _registerWishlist($wishlist)
    {
        try{
            $emailWishlist = Mage::getModel('email_connector/wishlist');
            $customer = Mage::getModel('customer/customer');

            //if wishlist exist not to save again
            if(!$emailWishlist->getWishlist($wishlist['wishlist_id'])){
                $customer->load($wishlist['customer_id']);
                $emailWishlist->setWishlistId($wishlist['wishlist_id'])
                    ->setCustomerId($wishlist['customer_id'])
                    ->setStoreId($customer->getStoreId())
                    ->save();
                // send customer to automation mapped
                $automationType = 'XML_PATH_CONNECTOR_AUTOMATION_STUDIO_WISHLIST';
                $email      = $customer->getEmail();
                $websiteId  = $customer->getWebsiteId();
                $this->_postCustomerToAutomation($email, $websiteId, $automationType);
            }
        }catch(Exception $e){
            Mage::logException($e);
        }
    }

    /**
     * wishlist item save after
     *
     * @param Varien_Event_Observer $observer
     */
    public function wishlistItemSaveAfter(Varien_Event_Observer $observer)
    {
	    $object        = $observer->getEvent()->getDataObject();
	    $wishlist      = Mage::getModel( 'wishlist/wishlist' )->load( $object->getWishlistId() );
	    $emailWishlist = Mage::getModel( 'email_connector/wishlist' );
	    try {
		    if ( $object->getWishlistId() ) {
			    $itemCount = count( $wishlist->getItemCollection() );
			    $item      = $emailWishlist->getWishlist( $object->getWishlistId() );

			    if ( $item && $item->getId() ) {
				    $preSaveItemCount = $item->getItemCount();

				    if ( $itemCount != $item->getItemCount() ) {
					    $item->setItemCount( $itemCount );
				    }

				    if ( $itemCount == 1 && $preSaveItemCount == 0 ) {
					    $item->setWishlistImported( null );
				    } elseif ( $item->getWishlistImported() ) {
					    $item->setWishlistModified( 1 );
				    }

				    $item->save();
			    }
		    }
	    } catch ( Exception $e ) {
		    Mage::logException( $e );
	    }

    }

    /**
     * wishlist delete observer
     *
     * @param Varien_Event_Observer $observer
     */
    public function wishlistDeleteAfter(Varien_Event_Observer $observer)
    {
        $object = $observer->getEvent()->getDataObject();
        $customer = Mage::getModel('customer/customer')->load($object->getCustomerId());
        $website = Mage::app()->getStore($customer->getStoreId())->getWebsite();
        $client = Mage::helper('connector')->getWebsiteApiClient($website);

         //Remove wishlist
        try{
            $item = Mage::getModel('email_connector/wishlist')->getWishlist($object->getWishlistId());
            if ($item->getId()) {
                $result = $client->deleteContactsTransactionalData($item->getId(), 'Wishlist');
                if (!isset($result->message)){
                    $item->delete();
                }
            }
        }catch (Exception $e){
            Mage::logException($e);
        }
    }
}