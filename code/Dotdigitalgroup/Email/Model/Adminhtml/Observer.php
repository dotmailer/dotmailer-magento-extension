<?php

class Dotdigitalgroup_Email_Model_Adminhtml_Observer
{

    /**
     * API Sync and Data Mapping.
     * Reset contacts for reimport.
     *
     * @return $this
     */
    public function actionConfigResetContacts()
    {
        $contactModel = Mage::getModel('ddg_automation/contact');
        $numImported  = $contactModel->getNumberOfImportedContacs();
        $updated      = $contactModel->getResource()->resetAllContacts();
        Mage::helper('ddg')->log(
            '-- Imported contacts: ' . $numImported . ' reseted :  ' . $updated
            . ' --'
        );

        /**
         * check for addressbook mapping and disable if no address selected.
         */
        $this->_checkAddressBookMapping(
            Mage::app()->getRequest()->getParam('website')
        );

        return $this;
    }


    /**
     * API Credentials.
     * Installation and validation confirmation.
     *
     * @return $this
     */
    public function actionConfigSaveApi()
    {
        $groups = Mage::app()->getRequest()->getPost('groups');
        if (isset($groups['api']['fields']['username']['inherit'])
            || isset($groups['api']['fields']['password']['inherit'])
        ) {
            return $this;
        }

        $apiUsername = isset($groups['api']['fields']['username']['value'])
            ? $groups['api']['fields']['username']['value'] : false;
        $apiPassword = isset($groups['api']['fields']['password']['value'])
            ? $groups['api']['fields']['password']['value'] : false;
        //skip if the inherit option is selected
        if ($apiUsername && $apiPassword) {
            Mage::helper('ddg')->log('----VALIDATING ACCOUNT---');
            $testModel = Mage::getModel('ddg_automation/apiconnector_test');
            $isValid   = $testModel->validate($apiUsername, $apiPassword);
            $config    = Mage::getConfig();
            if ( ! $isValid) {
                /**
                 * Disable invalid Api credentials
                 */
                $scopeId = 0;
                if ($website = Mage::app()->getRequest()->getParam('website')) {
                    $scope   = 'websites';
                    $scopeId = Mage::app()->getWebsite($website)->getId();
                } else {
                    $scope = "default";
                }
                $config->saveConfig(
                    Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_API_ENABLED,
                    0, $scope, $scopeId
                );
            }

            //check if returned value is an object
            if (is_object($isValid)) {
                //save endpoint for account
                foreach ($isValid->properties as $property) {
                    if ($property->name == 'ApiEndpoint'
                        && strlen(
                            $property->value
                        )
                    ) {
                        $config->saveConfig(
                            Dotdigitalgroup_Email_Helper_Config::PATH_FOR_API_ENDPOINT,
                            $property->value
                        );
                        break;
                    }
                }
            }
            $config->cleanCache();
        }

        return $this;
    }

    protected function _checkAddressBookMapping($website)
    {

        $helper                = Mage::helper('ddg');
        $customerAddressBook   = $helper->getWebsiteConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOMERS_ADDRESS_BOOK_ID,
            $website
        );
        $subscriberAddressBook = $helper->getWebsiteConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SUBSCRIBERS_ADDRESS_BOOK_ID,
            $website
        );

        if ( ! $customerAddressBook
            && $helper->getWebsiteConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_CONTACT_ENABLED,
                $website
            )
        ) {

            $helper->disableConfigForWebsite(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_CONTACT_ENABLED
            );
            Mage::getSingleton('adminhtml/session')->addNotice(
                'The Contact Sync Disabled - No Addressbook Selected !'
            );
        }
        if ( ! $subscriberAddressBook
            && $helper->getWebsiteConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_SUBSCRIBER_ENABLED,
                $website
            )
        ) {
            $helper->disableConfigForWebsite(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_SUBSCRIBER_ENABLED
            );
            Mage::getSingleton('adminhtml/session')->addNotice(
                'The Subscriber Sync Disabled - No Addressbook Selected !'
            );
        }

    }

    /**
     * Check for name option in array.
     *
     * @param $name
     * @param $data
     *
     * @return bool
     */
    protected function _checkForOption($name, $data)
    {
        //loop for all options
        foreach ($data as $one) {

            if ($one->name == $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * Update Feed for latest releases.
     *
     */
    public function updateFeed()
    {
        Mage::getModel('ddg_automation/feed')->checkForUpgrade();
    }


    /**
     * Add modified segment for contact.
     *
     * @param $observer
     *
     * @return $this
     */
    public function connectorCustomerSegmentChanged($observer)
    {
        $segmentsIds = $observer->getEvent()->getSegmentIds();
        $customerId  = Mage::getSingleton('customer/session')->getCustomerId();
        $websiteId   = Mage::app()->getStore()->getWebsiteId();

        if ( ! empty($segmentsIds) && $customerId) {
            $this->addContactsFromWebsiteSegments(
                $customerId, $segmentsIds, $websiteId
            );
        }

        return $this;
    }


    /**
     * Add segment ids.
     *
     * @param $customerId
     * @param $segmentIds
     * @param $websiteId
     *
     * @return $this
     */
    protected function addContactsFromWebsiteSegments($customerId, $segmentIds,
        $websiteId
    ) 
    {

        if (empty($segmentIds) || ! $customerId) {
            return $this;
        }
        $segmentIds = implode(',', $segmentIds);

        $contact = Mage::getModel('ddg_automation/contact')->getCollection()
            ->addFieldToFilter('customer_id', $customerId)
            ->addFieldToFilter('website_id', $websiteId)
            ->setPageSize(1)
            ->getFirstItem();
        try {

            $contact->setSegmentIds($segmentIds)
                ->setEmailImported()
                ->save();

        } catch (Exception $e) {
            Mage::logException($e);
        }

        return $this;
    }

    protected function getCustomerSegmentIdsForWebsite($customerId, $websiteId)
    {
        $segmentIds = Mage::getModel('ddg_automation/contact')->getCollection()
            ->addFieldToFilter('website_id', $websiteId)
            ->addFieldToFilter('customer_id', $customerId)
            ->getFirstItem()
            ->getSegmentIds();

        return $segmentIds;
    }
}