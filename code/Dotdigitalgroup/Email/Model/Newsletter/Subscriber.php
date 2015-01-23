<?php

class Dotdigitalgroup_Email_Model_Newsletter_Subscriber
{
    const STATUS_SUBSCRIBED     = 1;
    const STATUS_NOT_ACTIVE     = 2;
    const STATUS_UNSUBSCRIBED   = 3;
    const STATUS_UNCONFIRMED    = 4;

    protected $_start;

    /**
     * Global number of subscriber updated.
     * @var
     */
    protected $_countSubscriber = 0;

    /**
     * SUBSCRIBER SYNC.
     * @return $this
     */
    public function sync()
    {
        $response = array('success' => true, 'message' => '');
        /** @var Dotdigitalgroup_Email_Helper_Data $helper */
        $helper = Mage::helper('connector');

        $this->_start = microtime(true);

        foreach (Mage::app()->getWebsites(true) as $website) {
            //if subscriber is enabled and mapped
            $enabled = (bool)$website->getConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_SUBSCRIBER_ENABLED);
            $addressBook = $website->getConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SUBSCRIBERS_ADDRESS_BOOK_ID);

	        //enabled and mapped
            if ( $enabled && $addressBook ) {

	            //ready to start sync
	            if (!$this->_countSubscriber)
	                $helper->log('---------------------- Start subscriber sync -------------------');

                $numUpdated = $this->exportSubscribersPerWebsite($website);
                // show message for any number of customers
                if ($numUpdated)
                    $response['message'] .=  '</br>' . $website->getName() . ', updated subscribers = ' . $numUpdated;

            }
        }

        //global number of subscribers to set the message
        if ($this->_countSubscriber) {
            //reponse message
            $message = 'Total time for sync : ' . gmdate("H:i:s", microtime(true) - $this->_start);

            //put the message in front
            $message .= $response['message'];
            $result['message'] = $message;
        }

        return $response;
    }

    /**
     * Export subscriber per website.
     * @param Mage_Core_Model_Website $website
     *
     * @return int
     */
    public function exportSubscribersPerWebsite(Mage_Core_Model_Website $website)
    {
        $updated = 0;
        $fileHelper = Mage::helper('connector/file');
        $limit = $website->getConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_LIMIT);
        $subscribers = Mage::getModel('email_connector/contact')->getSubscribersToImport($website, $limit);
        if (count($subscribers)) {
            $client = Mage::helper('connector')->getWebsiteApiClient($website);
            $subscribersFilename = strtolower($website->getCode() . '_subscribers_' . date('d_m_Y_Hi') . '.csv');
            //get mapped storename
            $subscriberStorename = Mage::helper('connector')->getMappedStoreName($website);
            //file headers
            $fileHelper->outputCSV($fileHelper->getFilePath($subscribersFilename), array('Email', 'emailType', $subscriberStorename));
            foreach ($subscribers as $subscriber) {
                try{
                    $email = $subscriber->getEmail();
                    $subscriber->setSubscriberImported(1)->save();
                    $subscriber = Mage::getModel('newsletter/subscriber')->loadByEmail($email);
                    $storeName = Mage::app()->getStore($subscriber->getStoreId())->getName();
                    // save data for subscribers
                    $fileHelper->outputCSV($fileHelper->getFilePath($subscribersFilename), array($email, 'Html', $storeName));
                    $updated++;
                }catch (Exception $e){
                    Mage::logException($e);
                }
            }
            Mage::helper('connector')->log('Subscriber filename: ' . $subscribersFilename);
            //Add to subscriber address book
            $client->postAddressBookContactsImport($subscribersFilename, $website->getConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SUBSCRIBERS_ADDRESS_BOOK_ID));
            $fileHelper->archiveCSV($subscribersFilename);
        }
        //add updated number for the website
        $this->_countSubscriber += $updated;
        return $updated;
    }

    /**
     * Unsubscribe suppressed contacts.
     * @param bool $force set 10years old
     * @return mixed
     */
    public function unsubscribe($force = false)
    {
	    $limit = 5;
	    $max_to_select = 1000;
	    $result['customers'] = 0;
	    $helper = Mage::helper('connector');
	    $date = Mage::app()->getLocale()->date()->subHour(1);
        // force sync all customers
        if($force)
            $date = $date->subYear(10);
        // datetime format string
        $dateString = $date->toString(Zend_Date::W3C);
        /**
         * 1. Sync all suppressed for each store
         */
        foreach (Mage::app()->getWebsites(true) as $website) {

            $enabled = (bool)$website->getConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_API_ENABLED);
	        $client = Mage::helper('connector')->getWebsiteApiClient($website);

	        //no enabled and valid credentials
            if (! $enabled)
                continue;

            $contacts = array();
            $skip = $i = 0;

            //there is a maximum of request we need to loop to get more suppressed contacts
            for ($i=0; $i<= $limit;$i++) {
                $apiContacts = $client->getContactsSuppressedSinceDate($dateString, $max_to_select , $skip);

                // skip no more contacts or the api request failed
                if(empty($apiContacts) || isset($apiContacts->message))
                    break;

                $contacts = array_merge($contacts, $apiContacts);
                $skip += 1000;
            }

            $subscriberBookId = $helper->getSubscriberAddressBook($website);

            // suppressed contacts to unsubscibe
            foreach ($contacts as $apiContact) {
                if (isset($apiContact->suppressedContact)) {
                    $suppressedContact = $apiContact->suppressedContact;
                    $email      = $suppressedContact->email;
                    $contactId  = $suppressedContact->id;
                    try{
                        /**
                         * 2. Remove subscriber from the address book.
                         */
                        $subscriber = Mage::getModel('newsletter/subscriber')->loadByEmail($email);
                        if ($subscriber->getStatus() == Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED) {
                            $subscriber->setStatus(Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED);
                            $subscriber->save();
                            // remove from subscriber address-book
                            $client->deleteAddressBookContact($subscriberBookId, $contactId);
                        }
                        //mark contact as suppressed and unsubscribe
                        $contactCollection = Mage::getModel('email_connector/contact')->getCollection()
                            ->addFieldToFilter('email', $email)
                            ->addFieldToFilter('website_id', $website->getId());
                        //unsubscribe from the email contact table.
                        foreach ($contactCollection as $contact) {
                            $contact->setIsSubscriber(null)
                                ->setSuppressed('1')->save();
                        }
                    }catch (Exception $e){
                        Mage::logException($e);
                    }
                }
            }
        }
        return $result;
    }
}