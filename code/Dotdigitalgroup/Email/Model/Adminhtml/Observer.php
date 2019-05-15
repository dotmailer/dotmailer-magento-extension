<?php

class Dotdigitalgroup_Email_Model_Adminhtml_Observer
{
    /**
     * @var int
     */
    private $storeId;

    /**
     * @var int
     */
    private $websiteId;

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
            '-- Imported contacts: ' . $numImported . ' reseted :  ' . $updated . ' --'
        );

        /**
         * Check for addressbook mapping and disable if no address selected.
         */
        $this->checkAddressBookMapping(Mage::app()->getRequest()->getParam('website'));

        return $this;
    }

    /**
     * * Check for mapping configuration, and disable subscriber/contact sync if not mapped.
     *
     * @param $website
     * @throws Mage_Core_Exception
     */
    protected function checkAddressBookMapping($website)
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

        if (! $customerAddressBook
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

        if (! $subscriberAddressBook
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
        $scopeId     = 0;
        if ($website = Mage::app()->getRequest()->getParam('website')) {
            $scope   = 'websites';
            $scopeId = Mage::app()->getWebsite($website)->getId();
        } else {
            $scope = "default";
        }

        $apiPassword = Mage::helper('ddg')->getApiPassword($website);

        //skip if the inherit option is selected
        if ($apiUsername && $apiPassword) {
            Mage::helper('ddg')->log('----VALIDATING ACCOUNT---');
            $testModel = Mage::getModel('ddg_automation/apiconnector_test');

            $isValid = $testModel->validate($apiUsername, $apiPassword);
            $config  = Mage::getConfig();

            if (! $isValid) {
                /**
                 * Disable invalid Api credentials
                 */
                $config->saveConfig(
                    Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_API_ENABLED,
                    0, $scope, $scopeId
                );
            } elseif (is_object($isValid)) {
                //save endpoint for account
                $this->saveApiEndpoint($apiUsername, $apiPassword, $scopeId);
            }

            $config->cleanCache();
        }

        return $this;
    }

    /**
     * Save api endpoint
     *
     * @param $apiUsername
     * @param $apiPassword
     * @param $website
     */
    public function saveApiEndpoint($apiUsername, $apiPassword, $website = 0)
    {
        $helper = Mage::helper('ddg');
        $website = Mage::app()->getWebsite($website);
        $client = $helper->getWebsiteApiClient($website);
        if ($client) {
            $client->setApiUsername($apiUsername)
                ->setApiPassword($apiPassword);
            $apiEndpoint = $helper->getApiEndPointFromApi($client);
            if ($apiEndpoint) {
                $helper->saveApiEndpoint($apiEndpoint, $website->getId());
            }
        }
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

        if (! empty($segmentsIds) && $customerId) {
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
    protected function addContactsFromWebsiteSegments($customerId, $segmentIds, $websiteId)
    {
        if (empty($segmentIds) || !$customerId) {
            return $this;
        }

        $segmentIds = implode(',', $segmentIds);

        try {
            //@codingStandardsIgnoreStart
            $contact = Mage::getModel('ddg_automation/contact')->getCollection()
                ->addFieldToFilter('customer_id', $customerId)
                ->addFieldToFilter('website_id', $websiteId)
                ->setPageSize(1)
                ->getFirstItem();
            //@codingStandardsIgnoreEnd

            $contact->setSegmentIds($segmentIds)
                ->setEmailImported()
                ->save();
        } catch (Exception $e) {
            Mage::logException($e);
        }

        return $this;
    }

}