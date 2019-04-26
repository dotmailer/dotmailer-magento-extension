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
        /** @var Mage_Newsletter_Model_Subscriber $subscriber */
        $subscriber = $observer->getEvent()->getSubscriber();
        $email = $subscriber->getEmail();
        $storeId = $subscriber->getStoreId();
        $subscriberStatus = $subscriber->getSubscriberStatus();
        $websiteId = Mage::app()->getStore($subscriber->getStoreId())->getWebsiteId();

        //check if enabled
        if (!Mage::helper('ddg')->isEnabled($websiteId)) {
            return $this;
        }

        try {
            $contactEmail = Mage::getModel('ddg_automation/contact')
                ->loadContactByStoreId($email, $storeId);

            if (! $contactEmail->getId()) {
                $contactEmail->setStoreId($storeId)
                    ->setWebsiteId($websiteId)
                    ->setEmail($email);
            }
            // only for subscribers
            if ($subscriberStatus == Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED) {
                //update subscriber status and reset the import
                $contactEmail->setSubscriberStatus($subscriberStatus)
                    ->setSubscriberImported(null)
                    ->setIsSubscriber('1');

                //Subscriber subscribed when it is suppressed in table then re-subscribe
                if ($contactEmail->getSuppressed()) {
                    Mage::getModel('ddg_automation/importer')->registerQueue(
                        Dotdigitalgroup_Email_Model_Importer::IMPORT_TYPE_SUBSCRIBER_RESUBSCRIBED,
                        array('email' => $email),
                        Dotdigitalgroup_Email_Model_Importer::MODE_SUBSCRIBER_RESUBSCRIBED,
                        $websiteId
                    );
                    //Set to subscriber imported and reset the subscriber as suppressed
                    $contactEmail->setSubscriberImported(1)
                        ->setSuppressed(null);
                }

                //save contact
                $contactEmail->save();

                //not subscribed
            } else {
                $unsubscribeEmail = Mage::registry('unsubscribeEmail');
                if ($unsubscribeEmail) {
                    //un-register
                    Mage::unregister('unsubscribeEmail');
                    if ($email == $unsubscribeEmail) {
                        return $this;
                    }
                }

                //skip if contact is suppressed
                if ($contactEmail->getSuppressed()) {
                    return $this;
                }

                $contactEmail->setIsSubscriber(null)
                    ->setSubscriberStatus(Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED);
                //save contact
                $contactEmail->save();

                if ($subscriberStatus == Mage_Newsletter_Model_Subscriber::STATUS_NOT_ACTIVE or
                    $subscriberStatus == Mage_Newsletter_Model_Subscriber::STATUS_UNCONFIRMED) {
                    return $this;
                }

                //Add subscriber update to importer queue
                Mage::getModel('ddg_automation/importer')->registerQueue(
                    Dotdigitalgroup_Email_Model_Importer::IMPORT_TYPE_SUBSCRIBER_UPDATE,
                    array('email' => $email, 'id' => $contactEmail->getId()),
                    Dotdigitalgroup_Email_Model_Importer::MODE_SUBSCRIBER_UPDATE,
                    $websiteId
                );
            }

            // fix for a multiple hit of the observer
            $emailReg = Mage::registry($email . '_subscriber_save');
            if ($emailReg) {
                return $this;
            }

            Mage::register($email . '_subscriber_save', $email);

            //add subscriber to automation
            if ($subscriber->isObjectNew() || $this->hasStatusChangedToSubscribed($subscriber)) {
                $this->_addSubscriberToAutomation($email, $subscriber, $websiteId);
            }

        } catch (Exception $e) {
            Mage::logException($e);
        }

        return $this;
    }

    /**
     * Remove guest subscriber from contact table
     *
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function handleNewsletterSubscriberDelete(Varien_Event_Observer $observer)
    {
        /** @var Mage_Newsletter_Model_Subscriber $subscriber */
        $subscriber = $observer->getEvent()->getSubscriber();
        $email = $subscriber->getEmail();
        $storeId = $subscriber->getStoreId();
        $websiteId = Mage::app()->getStore($storeId)->getWebsiteId();
        $helper    = Mage::helper('ddg');

        //api enabled
        $enabled = $helper->getWebsiteConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_API_ENABLED,
            $websiteId
        );

        /**
         * Remove contact.
         */
        if ($enabled) {
            try {
                $contactModel = Mage::getModel('ddg_automation/contact')
                    ->loadContactByStoreId($email, $storeId);

                if ($contactModel->getId()) {
                    //If contact is a customer or guest order contact
                    if ($contactModel->getCustomerId() || $contactModel->getIsGuest()) {
                        //Add subscriber update to importer queue
                        Mage::getModel('ddg_automation/importer')->registerQueue(
                            Dotdigitalgroup_Email_Model_Importer::IMPORT_TYPE_SUBSCRIBER_UPDATE,
                            array('email' => $email, 'id' => $contactModel->getId()),
                            Dotdigitalgroup_Email_Model_Importer::MODE_SUBSCRIBER_UPDATE,
                            $websiteId
                        );

                        //Remove subscriber from contact
                        $contactModel->getResource()->updateSubscriberFromContact($email);
                    } else {
                        //Add to importer queue
                        Mage::getModel('ddg_automation/importer')
                            ->registerQueue(
                                Dotdigitalgroup_Email_Model_Importer::IMPORT_TYPE_CONTACT,
                                $email,
                                Dotdigitalgroup_Email_Model_Importer::MODE_CONTACT_DELETE,
                                $websiteId
                            );

                        //Remove contact
                        $contactModel->delete();
                    }
                }
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }

        return $this;
    }

    /**
     * @param $email
     * @param $subscriber
     * @param $websiteId
     */
    protected function _addSubscriberToAutomation($email, $subscriber, $websiteId)
    {
        $storeId = $subscriber->getStoreId();
        $store = Mage::app()->getStore($storeId);
        $programId = Mage::helper('ddg')
            ->getAutomationIdByType('XML_PATH_CONNECTOR_AUTOMATION_STUDIO_SUBSCRIBER', $websiteId);
        //not mapped ignore
        if (! $programId) {
            return;
        }

        try {
            //save subscriber to the queue
            $automation = Mage::getModel('ddg_automation/automation');
            $automation->setEmail($email)
                ->setAutomationType(Dotdigitalgroup_Email_Model_Automation::AUTOMATION_TYPE_NEW_SUBSCRIBER)
                ->setEnrolmentStatus(Dotdigitalgroup_Email_Model_Automation::AUTOMATION_STATUS_PENDING)
                ->setTypeId($subscriber->getId())
                ->setWebsiteId($websiteId)
                ->setStoreName($store->getName())
                ->setProgramId($programId);
            $automation->save();
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * @param Mage_Newsletter_Model_Subscriber $unsavedSubscriber
     * @return bool
     */
    private function hasStatusChangedToSubscribed($unsavedSubscriber)
    {
        $savedSubscriber = Mage::getModel('newsletter/subscriber')->load($unsavedSubscriber->getId());
        $originalStatus = $savedSubscriber->getSubscriberStatus();
        $newStatus = $unsavedSubscriber->getSubscriberStatus();

        return
            $originalStatus !== Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED &&
            $newStatus === Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED;
    }
    /**
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function frontendNewsletterSubscriberSave(Varien_Event_Observer $observer)
    {
        /** @var Mage_Newsletter_Model_Subscriber $subscriber */
        $subscriber = $observer->getEvent()->getSubscriber();
        $email = $subscriber->getEmail();

        $subscriberStatus = $subscriber->getSubscriberStatus();
        $websiteId = Mage::app()->getStore($subscriber->getStoreId())->getWebsiteId();

        //If not confirmed or not enabled.
        if ($subscriberStatus == Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED ||
            ! Mage::helper('ddg')->isEnabled($websiteId) ||
            ! Mage::helper('ddg/config')->isConsentSubscriberEnabled($websiteId)
        ) {
            return $this;
        }

        try {
            $emailContactId = Mage::getModel('ddg_automation/contact')
                ->loadByCustomerEmail($email, $websiteId)
                ->getId();
            $consentModel = Mage::getModel('ddg_automation/consent');
            $consentResource = Mage::getResourceModel('ddg_automation/consent');
            $consentResource->load($consentModel,$emailContactId, 'email_contact_id');

            //don't update the consent data for guest subscribers or not confrimed
            if (! $consentModel->isObjectNew() ) {
                return $this;
            }

            $http = Mage::helper('core/http');
            $consentUrl = $http->getHttpReferer() ? $http->getHttpReferer()  : Mage::getUrl();
            $consentIp = $http->getRemoteAddr();
            $consentUserAgent = $http->getHttpUserAgent();
            $consentDatetime = Mage::getModel('core/date')->gmtDate();
            //save the consent data against the contact
            $consentModel->setEmailContactId($emailContactId)
                ->setConsentUrl($consentUrl)
                ->setConsentDatetime($consentDatetime)
                ->setConsentIp($consentIp)
                ->setConsentUserAgent($consentUserAgent);

            //save contact
            $consentResource->save($consentModel);

        } catch (\Exception $e) {
            Mage::helper('ddg')->log($e);
        }
    }

}