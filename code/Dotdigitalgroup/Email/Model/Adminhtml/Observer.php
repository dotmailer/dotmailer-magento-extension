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
     * @param $observer
     * @throws Mage_Core_Exception
     * @throws Mage_Core_Model_Store_Exception
     */
    public function actionConfigTemplatesChanged($observer)
    {
        $storeCode = $observer->getStore();
        $websiteCode = $observer->getWebsite();
        $groups = Mage::app()->getRequest()->getPost('groups');
        $dotTemplate = Mage::getModel('ddg_automation/template');
        $this->storeId = ($storeCode)? Mage::app()->getStore($storeCode)->getId() : '0';
        $this->websiteId = ($websiteCode)? Mage::app()->getWebsite($websiteCode)->getId() : '0';

        foreach ($groups['email_templates']['fields'] as $templateConfigId => $campaignId) {
            if (isset($groups['email_templates']['fields'][$templateConfigId]['inherit'])) {
                //remove the config value when the parent inherit was selected
                $this->removeConfigPathValue($dotTemplate->templateConfigMapping[$templateConfigId]);
                continue;
            }
            if (isset($campaignId['value'])) {
                //email template is mapped
                if ($campaignId = $campaignId['value']) {
                    $templateConfigPath = $dotTemplate->templateConfigMapping[$templateConfigId];

                    $template = $dotTemplate->saveTemplateWithConfigPath(
                        $templateConfigId,
                        $campaignId,
                        $this->websiteId,
                        $this->storeId
                    );
                    //save created new email template with the default config value for template
                    if ($template->getId()) {
                        $this->saveConfigPath($templateConfigPath, $template->getId());
                    }

                } else {
                    //reset core to default email template
                    $this->removeConfigPathValue($dotTemplate->templateConfigMapping[$templateConfigId]);
                    //remove the config for dotmailer template
                    $this->removeConfigPathValue(
                        $dotTemplate->templateConfigIdToDotmailerConfigPath[$templateConfigId]
                    );
                }
            }
        }
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
        if (empty($segmentIds) || ! $customerId) {
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


    /**
     * Save config value base on the current scope. Default will be use if there is no store or website id set.
     *
     * @param $configPath
     * @param $configValue
     */
    private function saveConfigPath($configPath, $configValue)
    {
        if ($this->storeId) {
            $scope = Mage_Adminhtml_Block_System_Config_Form::SCOPE_STORES;
            $scopeId = $this->storeId;
        } elseif ($this->websiteId) {
            $scope = Mage_Adminhtml_Block_System_Config_Form::SCOPE_WEBSITES;
            $scopeId = $this->websiteId;
        } else {
            $scope = Mage_Adminhtml_Block_System_Config_Form::SCOPE_DEFAULT;
            $scopeId = '0';
        }

        //save the config for new created template
        Mage::getConfig()->saveConfig(
            $configPath,
            $configValue,
            $scope,
            $scopeId
        );

        //clean the config cache
        Mage::getConfig()->reinit();
    }

    /**
     * Remove config path for current scope.
     *
     * @param $configPath
     */
    private function removeConfigPathValue($configPath)
    {
        if ($this->storeId) {
            $scope = Mage_Adminhtml_Block_System_Config_Form::SCOPE_STORES;
            $scopeId = $this->storeId;
        } elseif ($this->websiteId) {
            $scope = Mage_Adminhtml_Block_System_Config_Form::SCOPE_WEBSITES;
            $scopeId = $this->websiteId;
        } else {
            $scope = Mage_Adminhtml_Block_System_Config_Form::SCOPE_DEFAULT;
            $scopeId = '0';
        }

        //remove the mapped config for the template;
        Mage::getConfig()->deleteConfig(
            $configPath,
            $scope,
            $scopeId
        );
    }

}