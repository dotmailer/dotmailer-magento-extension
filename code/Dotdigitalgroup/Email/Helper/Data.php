<?php

class Dotdigitalgroup_Email_Helper_Data extends Mage_Core_Helper_Abstract
{

    /**
     * Api enabled for website.
     *
     * @param int $website
     * @return bool
     */
    public function isEnabled($website = 0)
    {
        $website = Mage::app()->getWebsite($website);

        return (bool)$website->getConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_API_ENABLED
        );
    }

    /**
     * @param $storeId
     * @return bool
     */
    public function isStoreEnabled($storeId)
    {
        $store = Mage::app()->getStore($storeId);

        return (bool)$store->getConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_API_ENABLED
        );
    }

    /**
     * @param int /object $website
     *
     * @return mixed
     */
    public function getApiUsername($website = 0)
    {
        $website = Mage::app()->getWebsite($website);

        return $website->getConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_API_USERNAME
        );
    }

    /**
     * @param int $website
     * @return string
     */
    public function getApiPassword($website = 0)
    {
        $website = Mage::app()->getWebsite($website);

        return Mage::helper('core')->decrypt(
            $website->getConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_API_PASSWORD
            )
        );
    }

    /**
     * @param $authRequest
     * @return bool
     */
    public function auth($authRequest)
    {
        if ($authRequest != Mage::getStoreConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_DYNAMIC_CONTENT_PASSCODE
        )) {
            return false;
        }

        return true;
    }

    /**
     * @return mixed
     */
    public function getMappedCustomerId()
    {
        return Mage::getStoreConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_MAPPING_CUSTOMER_ID
        );
    }

    /**
     * @return mixed
     */
    public function getMappedOrderId()
    {
        return Mage::getStoreConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_MAPPING_LAST_ORDER_ID
        );
    }

    /**
     * @return mixed
     */
    public function getPasscode()
    {
        return Mage::getStoreConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_DYNAMIC_CONTENT_PASSCODE
        );
    }

    /**
     * @return mixed
     */
    public function getLastOrderId()
    {
        return Mage::getStoreConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_MAPPING_LAST_ORDER_ID
        );
    }

    /**
     * @return mixed
     */
    public function getLastQuoteId()
    {
        return Mage::getStoreConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_MAPPING_LAST_QUOTE_ID
        );

    }

    /**
     * @param $data
     * @param int $level
     * @param string $filename
     * @return $this
     */
    public function log($data, $level = Zend_Log::DEBUG, $filename = 'api.log')
    {
        if ($this->getDebugEnabled()) {
            $filename = 'connector_' . $filename;

            Mage::log($data, $level, $filename, $force = true);
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function getDebugEnabled()
    {
        return (bool)Mage::getStoreConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_ADVANCED_DEBUG_ENABLED
        );
    }

    /**
     * Extension version number.
     *
     * @return string
     */
    public function getConnectorVersion()
    {
        $modules = (array)Mage::getConfig()->getNode('modules')->children();
        if (isset($modules['Dotdigitalgroup_Email'])) {
            $moduleName = $modules['Dotdigitalgroup_Email'];

            return (string)$moduleName->version;
        }

        return '';
    }


    /**
     * @return bool
     */
    public function getPageTrackingEnabled()
    {
        return (bool)Mage::getStoreConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_PAGE_TRACKING_ENABLED
        );
    }

    /**
     * @return bool
     */
    public function getRoiTrackingEnabled()
    {
        return (bool)Mage::getStoreConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_ROI_TRACKING_ENABLED
        );
    }

    /**
     * @return bool
     */
    public function getResourceAllocationEnabled()
    {
        return (bool)Mage::getStoreConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_RESOURCE_ALLOCATION
        );
    }

    /**
     * @param $website
     * @return string
     */
    public function getMappedStoreName($website)
    {
        $mapped = $website->getConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_MAPPING_CUSTOMER_STORENAME
        );
        $storeName = ($mapped) ? $mapped : '';

        return $storeName;
    }

    /**
     * Get the contact id for the customer based on website id.
     *
     * @param $email
     * @param $websiteId
     *
     * @return bool|string
     */
    public function getContactId($email, $websiteId)
    {
        $contact = Mage::getModel('ddg_automation/contact')
            ->loadByCustomerEmail($email, $websiteId);

        $client = $this->getWebsiteApiClient($websiteId);
        $response = $client->postContacts($email);

        if (isset($response->message)) {
            $contact->setEmailImported(1);
            if ($response->message == Dotdigitalgroup_Email_Model_Apiconnector_Client::API_ERROR_CONTACT_SUPPRESSED) {
                $contact->setSuppressed(1);
            }

            $contact->save();

            return false;
        }

        //save contact id
        if (isset($response->id)) {
            $contact->setContactId($response->id)
                ->save();

            return $response->id;
        } else {
            false;
        }
    }

    /**
     * @param $website
     * @return mixed
     */
    public function getCustomerAddressBook($website)
    {
        $website = Mage::app()->getWebsite($website);

        return $website->getConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOMERS_ADDRESS_BOOK_ID
        );
    }

    /**
     * @param $website
     * @return mixed
     */
    public function getSubscriberAddressBook($website)
    {
        $website = Mage::app()->getWebsite($website);

        return $website->getConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SUBSCRIBERS_ADDRESS_BOOK_ID
        );
    }

    /**
     * @param $website
     * @return mixed
     */
    public function getGuestAddressBook($website)
    {
        $website = Mage::app()->getWebsite($website);

        return $website->getConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_GUEST_ADDRESS_BOOK_ID
        );
    }

    /**
     * @return $this
     */
    public function allowResourceFullExecution()
    {
        if ($this->getResourceAllocationEnabled()) {
            //@codingStandardsIgnoreStart
            /* it may be needed to set maximum execution time of the script to longer,
             * like 60 minutes than usual */
            set_time_limit(7200);

            /* and memory to 512 megabytes */
            ini_set('memory_limit', '512M');
            //@codingStandardsIgnoreEnd
        }

        return $this;
    }

    /**
     * @param $size
     * @return string
     */
    public function convert($size)
    {
        $unit = array('b', 'kb', 'mb', 'gb', 'tb', 'pb');

        return round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' '
            . $unit[(int)$i];
    }

    /**
     * @return string
     */
    public function getStringWebsiteApiAccounts()
    {
        $accounts = array();
        foreach (Mage::app()->getWebsites() as $website) {
            $websiteId = $website->getId();
            $apiUsername = $this->getApiUsername($website);
            $accounts[$apiUsername] = $apiUsername . ', websiteId: '
                . $websiteId . ' name ' . $website->getName();
        }

        return implode('</br>', $accounts);
    }

    /**
     * @param int $website
     *
     * @return array|mixed
     * @throws Mage_Core_Exception
     */
    public function getCustomAttributes($website = 0)
    {
        $website = Mage::app()->getWebsite($website);
        $attr = $website->getConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_MAPPING_CUSTOM_DATAFIELDS
        );

        if (!$attr) {
            return array();
        }

        //@codingStandardsIgnoreStart
        return unserialize($attr);
        //@codingStandardsIgnoreEnd
    }


    /**
     * Enterprise custom datafields attributes.
     *
     * @param int $website
     *
     * @return array
     * @throws Mage_Core_Exception
     */
    public function getEnterpriseAttributes($website = 0)
    {
        $website = Mage::app()->getWebsite($website);
        $result = array();
        $attrs = $website->getConfig(
            'connector_data_mapping/enterprise_data'
        );

        if (is_array($attrs)) {
            //get individual mapped keys
            foreach ($attrs as $key => $one) {
                $config = $website->getConfig(
                    'connector_data_mapping/enterprise_data/' . $key
                );
                //check for the mapped field
                if ($config) {
                    $result[$key] = $config;
                }
            }
        }

        return $result;
    }

    /**
     * @param                                              $path
     * @param null|string|bool|int|Mage_Core_Model_Website $websiteId
     *
     * @return mixed
     */
    public function getWebsiteConfig($path, $websiteId = 0)
    {
        $website = Mage::app()->getWebsite($websiteId);

        return $website->getConfig($path);
    }

    /**
     * Api client by website.
     *
     * @param mixed $website
     *
     * @return bool|Dotdigitalgroup_Email_Model_Apiconnector_Client
     */
    public function getWebsiteApiClient($website = 0)
    {
        if (!$this->isEnabled($website)) {
            return false;
        }

        if (!$this->getApiUsername($website)
            || !$this->getApiPassword($website)
        ) {
            return false;
        }

        $client = Mage::getModel('ddg_automation/apiconnector_client');
        $client->setApiUsername($this->getApiUsername($website))
            ->setApiPassword($this->getApiPassword($website));

        $websiteId = Mage::app()->getWebsite($website)->getId();

        //Get api endpoint
        $apiEndpoint = $this->getApiEndpoint($websiteId, $client);

        //Set api endpoint on client
        if ($apiEndpoint) {
            $client->setApiEndpoint($apiEndpoint);
        }

        return $client;
    }

    /**
     * Get Api endPoint
     *
     * @param $websiteId
     * @param $client
     * @return mixed
     */
    public function getApiEndpoint($websiteId, $client)
    {
        //Get from DB
        $apiEndpoint = $this->getApiEndPointFromConfig($websiteId);

        //Nothing from DB then fetch from api
        if (!$apiEndpoint) {
            $apiEndpoint = $this->getApiEndPointFromApi($client);
            //Save it in DB
            if ($apiEndpoint) {
                $this->saveApiEndpoint($apiEndpoint, $websiteId);
            }
        }

        return $apiEndpoint;
    }

    public function getRegionPrefix()
    {
        $websiteId = Mage::app()->getStore()->getWebsiteId();
        $client = $this->getWebsiteApiClient($websiteId);
        if (!$client) {
            return '';
        }

        $apiEndpoint = $this->getApiEndpoint($websiteId, $client);
        preg_match("/https:\/\/(.*)api.dotmailer.com/", $apiEndpoint, $matches);
        return $matches[1];
    }
    /**
     * Get api end point from api
     *
     * @param Dotdigitalgroup_Email_Model_Apiconnector_Client $client
     * @return mixed
     */
    public function getApiEndPointFromApi($client)
    {
        $accountInfo = $client->getAccountInfo();
        $apiEndpoint = false;
        if (is_object($accountInfo) && !isset($accountInfo->message)) {
            foreach ($accountInfo->properties as $property) {
                if ($property->name == 'ApiEndpoint' && !empty($property->value)) {
                    $apiEndpoint = $property->value;
                    break;
                }
            }
        }

        return $apiEndpoint;
    }

    /**
     * Get api end point for given website
     *
     * @param $websiteId
     * @return mixed
     */
    public function getApiEndPointFromConfig($websiteId)
    {
        $apiEndpoint = $this->getWebsiteConfig(
            Dotdigitalgroup_Email_Helper_Config::PATH_FOR_API_ENDPOINT,
            $websiteId
        );
        return $apiEndpoint;
    }

    /**
     * Save api endpoint into config.
     *
     * @param $apiEndpoint
     * @param $websiteId
     */
    public function saveApiEndpoint($apiEndpoint, $websiteId = 0)
    {
        if ($websiteId == 0) {
            $scope = 'default';
        } else {
            $scope = 'websites';
        }

        $config = Mage::getModel('core/config');
        $config->saveConfig(
            Dotdigitalgroup_Email_Helper_Config::PATH_FOR_API_ENDPOINT,
            $apiEndpoint,
            $scope,
            $websiteId
        );
        $config->cleanCache();
    }

    /**
     * Authorisation url for OAUTH.
     *
     * @return string
     */
    public function getAuthoriseUrl()
    {
        $clientId = Mage::getStoreConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CLIENT_ID
        );

        //callback uri if not set custom
        $redirectUri = $this->getRedirectUri();
        $redirectUri .= 'connector/email/callback';
        $adminUser = Mage::getSingleton('admin/session')->getUser();
        //query params
        $params = array(
            'redirect_uri' => $redirectUri,
            'scope' => 'Account',
            'state' => $adminUser->getId(),
            'response_type' => 'code'
        );

        $authorizeBaseUrl = Mage::helper('ddg/config')->getAuthorizeLink();
        $url = $authorizeBaseUrl . http_build_query($params)
            . '&client_id=' . $clientId;

        return $url;
    }

    /**
     * @return mixed|string
     */
    public function getRedirectUri()
    {
        $callback = Mage::helper('ddg/config')->getCallbackUrl();

        return $callback;
    }

    /**
     * Order status config value.
     *
     * @param int $website
     *
     * @return mixed order status
     */
    public function getConfigSelectedStatus($website = 0)
    {
        $status = $this->getWebsiteConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_ORDER_STATUS,
            $website
        );
        if ($status) {
            return explode(',', $status);
        } else {
            return false;
        }
    }

    /**
     * @param int $website
     * @return array|bool
     */
    public function getConfigSelectedCustomOrderAttributes($website = 0)
    {
        $customAttributes = $this->getWebsiteConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOM_ORDER_ATTRIBUTES,
            $website
        );
        if ($customAttributes) {
            return explode(',', $customAttributes);
        } else {
            return false;
        }
    }

    /**
     * @param int $website
     * @return array|bool
     */
    public function getConfigSelectedCustomQuoteAttributes($website = 0)
    {
        $customAttributes = $this->getWebsiteConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOM_QUOTE_ATTRIBUTES,
            $website
        );
        if ($customAttributes) {
            return explode(',', $customAttributes);
        } else {
            return false;
        }
    }

    /**
     * Check sweet tooth installed/active status.
     *
     * @return boolean
     */
    public function isSweetToothEnabled()
    {
        return (bool)Mage::getConfig()->getModuleConfig('TBT_Rewards')->is('active', 'true');
    }

    /**
     * Check sweet tooth installed/active status and active status.
     *
     * @param Mage_Core_Model_Website $website
     *
     * @return boolean
     */
    public function isSweetToothToGo($website)
    {
        $stMappingStatus = $this->getWebsiteConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_MAPPING_SWEETTOOTH_ACTIVE,
            $website
        );
        if ($stMappingStatus && $this->isSweetToothEnabled()) {
            return true;
        }

        return false;
    }

    /**
     * @param $customerId
     */
    public function setConnectorContactToReImport($customerId)
    {
        try {
            $coreResource = Mage::getSingleton('core/resource');
            $con = $coreResource->getConnection('core_write');
            $con->update(
                $coreResource->getTableName('ddg_automation/contact'),
                array('email_imported' => new Zend_Db_Expr('null')),
                array("customer_id = ?" => $customerId)
            );
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * Diff between to times.
     *
     * @param      $timeOne
     * @param null $timeTwo
     *
     * @return int
     */
    public function dateDiff($timeOne, $timeTwo = null)
    {
        if ($timeTwo === null) {
            //@codingStandardsIgnoreStart
            $timeTwo = Mage::getModel('core/date')->date();
            //@codingStandardsIgnoreEnd
        }

        $timeOne = strtotime($timeOne);
        $timeTwo = strtotime($timeTwo);

        return $timeTwo - $timeOne;
    }


    /**
     * Disable website config when the request is made admin area only!
     *
     * @param $path
     *
     * @throws Mage_Core_Exception
     */
    public function disableConfigForWebsite($path)
    {
        $scopeId = 0;
        if ($website = Mage::app()->getRequest()->getParam('website')) {
            $scope = 'websites';
            $scopeId = Mage::app()->getWebsite($website)->getId();
        } else {
            $scope = "default";
        }

        $config = Mage::getConfig();
        $config->saveConfig($path, 0, $scope, $scopeId);
        $config->cleanCache();
    }

    /**
     * Number of customers with duplicate emails, emails as total number.
     *
     * @return Mage_Customer_Model_Resource_Customer_Collection
     */
    public function getCustomersWithDuplicateEmails()
    {
        $customers = Mage::getModel('customer/customer')->getCollection();

        //@codingStandardsIgnoreStart
        //duplicate emails
        $customers->getSelect()
            ->columns(array('emails' => 'COUNT(e.entity_id)'))
            ->group('email')
            ->having('emails > ?', 1);
        //@codingStandardsIgnoreEnd
        return $customers;
    }

    /**
     * Generate the baseurl for the default store
     * dynamic content will be displayed.
     *
     * @return string
     * @throws Mage_Core_Exception
     */
    public function generateDynamicUrl()
    {
        $website = Mage::app()->getRequest()->getParam('website', false);

        //set website url for the default store id
        $website = ($website) ? Mage::app()->getWebsite($website) : 0;

        $defaultGroup = Mage::app()->getWebsite($website)
            ->getDefaultGroup();

        if (!$defaultGroup) {
            return $mage = Mage::app()->getStore()->getBaseUrl(
                Mage_Core_Model_Store::URL_TYPE_WEB
            );
        }

        //base url
        $baseUrl = Mage::app()->getStore($defaultGroup->getDefaultStore())
            ->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);

        return $baseUrl;
    }

    /**
     * @param int $store
     *
     * @return mixed
     */
    public function isNewsletterSuccessDisabled($store = 0)
    {
        return Mage::getStoreConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_DISABLE_NEWSLETTER_SUCCESS,
            $store
        );
    }

    /**
     * @return bool
     */
    public function getEasyEmailCapture()
    {
        return Mage::getStoreConfigFlag(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_EMAIL_CAPTURE
        );
    }

    /**
     * @return bool
     */
    public function getEasyEmailCaptureForNewsletter()
    {
        return Mage::getStoreConfigFlag(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_EMAIL_CAPTURE_NEWSLETTER
        );
    }

    /**
     * Get feefo logon config value.
     *
     * @return mixed
     */
    public function getFeefoLogon()
    {
        return $this->getWebsiteConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_REVIEWS_FEEFO_LOGON
        );
    }

    /**
     * Get feefo reviews limit config value.
     *
     * @return mixed
     */
    public function getFeefoReviewsPerProduct()
    {
        return $this->getWebsiteConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_REVIEWS_FEEFO_REVIEWS
        );
    }

    /**
     * Get feefo logo template config value.
     *
     * @return mixed
     */
    public function getFeefoLogoTemplate()
    {
        return $this->getWebsiteConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_REVIEWS_FEEFO_TEMPLATE
        );
    }

    /**
     * @param $website
     *
     * @return string
     */
    public function getReviewDisplayType($website)
    {
        return $this->getWebsiteConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_DYNAMIC_CONTENT_REVIEW_DISPLAY_TYPE,
            $website
        );
    }

    /**
     * Update data fields.
     *
     * @param                         $email
     * @param Mage_Core_Model_Website $website
     * @param                         $storeName
     */
    public function updateDataFields($email, Mage_Core_Model_Website $website, $storeName)
    {
        $data = array();
        if ($storeNameKey = $website->getConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOMER_STORE_NAME
        )
        ) {
            $data[] = array(
                'Key' => $storeNameKey,
                'Value' => $storeName
            );
        }

        if ($websiteName = $website->getConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOMER_WEBSITE_NAME
        )
        ) {
            $data[] = array(
                'Key' => $websiteName,
                'Value' => $website->getName()
            );
        }

        if (!empty($data)) {
            //update data fields
            $client = $this->getWebsiteApiClient($website);
            if ($client instanceof Dotdigitalgroup_Email_Model_Apiconnector_Client) {
                $client->updateContactDatafieldsByEmail($email, $data);
            }
        }
    }

    /**
     * Check connector SMTP installed/active status.
     *
     * @return boolean
     */
    public function isSmtpEnabled()
    {
        return (bool)Mage::getConfig()->getModuleConfig('Ddg_Transactional')
            ->is('active', 'true');
    }

    /**
     * Is magento enterprise.
     *
     * @return bool
     */
    public function isEnterprise()
    {
        return Mage::getConfig()->getModuleConfig('Enterprise_Enterprise')
        && Mage::getConfig()->getModuleConfig('Enterprise_AdminGws')
        && Mage::getConfig()->getModuleConfig('Enterprise_Checkout')
        && Mage::getConfig()->getModuleConfig('Enterprise_Customer');

    }

    /**
     * Update last quote id datafield.
     *
     * @param $quoteId
     * @param $email
     * @param $websiteId
     */
    public function updateLastQuoteId($quoteId, $email, $websiteId)
    {
        $client = $this->getWebsiteApiClient($websiteId);
        //last quote id config data mapped from website level config
        $quoteIdField = $this->getWebsiteConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_MAPPING_LAST_QUOTE_ID,
            $websiteId
        );

        $data[] = array(
            'Key' => $quoteIdField,
            'Value' => $quoteId
        );
        if ($client instanceof Dotdigitalgroup_Email_Model_Apiconnector_Client) {
            //update datafields for contact
            $client->updateContactDatafieldsByEmail($email, $data);
        }
    }

    /**
     * @param int $websiteId
     *
     * @return bool
     */
    public function isOrderSyncEnabled($websiteId = 0)
    {
        return (bool)$this->getWebsiteConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_ORDER_ENABLED,
            $websiteId
        );
    }

    /**
     * @param int $websiteId
     *
     * @return bool
     */
    public function isCatalogSyncEnabled($websiteId = 0)
    {
        return (bool)$this->getWebsiteConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_CATALOG_ENABLED,
            $websiteId
        );
    }

    /**
     * @param int $websiteId
     *
     * @return bool
     */
    public function isContactSyncEnabled($websiteId = 0)
    {
        return (bool)$this->getWebsiteConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_CONTACT_ENABLED,
            $websiteId
        );
    }

    /**
     * @param int $websiteId
     *
     * @return bool
     */
    public function isGuestSyncEnabled($websiteId = 0)
    {
        return (bool)$this->getWebsiteConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_GUEST_ENABLED,
            $websiteId
        );
    }

    /**
     * @param int $websiteId
     *
     * @return bool
     */
    public function isSubscriberSyncEnabled($websiteId = 0)
    {
        return (bool)$this->getWebsiteConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_SUBSCRIBER_ENABLED,
            $websiteId
        );
    }

    /**
     * @return bool
     */
    public function getCronInstalled()
    {
        $lastCustomerSync = Mage::getModel('ddg_automation/cron')
            ->getLastCustomerSync();
        $timespan = Mage::helper('ddg')->dateDiff($lastCustomerSync);

        //last customer cron was less then 15 min
        if ($timespan <= 15 * 60) {
            return true;
        }

        return false;
    }

    /**
     * Get the config id by the automation type.
     *
     * @param     $automationType
     * @param int $websiteId
     *
     * @return mixed
     */
    public function getAutomationIdByType($automationType, $websiteId = 0)
    {
        $path = constant(
            'Dotdigitalgroup_Email_Helper_Config::' . $automationType
        );
        $automationCampaignId = $this->getWebsiteConfig($path, $websiteId);

        return $automationCampaignId;
    }

    /**
     * @return mixed
     */
    public function getAbandonedProductName()
    {
        return Mage::getStoreConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_ABANDONED_PRODUCT_NAME
        );

    }

    /**
     * Update last quote id datafield.
     *
     * @param $name
     * @param $email
     * @param $websiteId
     */
    public function updateAbandonedProductName($name, $email, $websiteId)
    {
        $client = $this->getWebsiteApiClient($websiteId);
        // id config data mapped
        $field = $this->getAbandonedProductName();

        if ($field && $client instanceof Dotdigitalgroup_Email_Model_Apiconnector_Client) {
            $data[] = array(
                'Key' => $field,
                'Value' => $name
            );
            //update data field for contact
            $client->updateContactDatafieldsByEmail($email, $data);
        }
    }


    /**
     * Api request response time limit that should be logged.
     *
     * @param int $websiteId
     *
     * @return mixed
     * @throws Mage_Core_Exception
     */
    public function getApiResponseTimeLimit($websiteId = 0)
    {
        return $this->getWebsiteConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_DEBUG_API_REQUEST_LIMIT,
            $websiteId
        );
    }

    /**
     * Main email for an account.
     *
     * @param int $website
     *
     * @return bool|string
     */
    public function getAccountEmail($website = 0)
    {
        $client = $this->getWebsiteApiClient($website);
        if ($client === false) {
            return false;
        }

        $info = $client->getAccountInfo();
        $email = '';

        if (isset($info->properties)) {
            $properties = $info->properties;

            foreach ($properties as $property) {
                if ($property->name == 'MainEmail') {
                    $email = $property->value;
                }
            }
        }

        return $email;
    }

    /**
     * @return bool
     */
    public function authIpAddress()
    {
        if ($ipString = Mage::getStoreConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_IP_RESTRICTION_ADDRESSES
        )
        ) {
            //string to array
            $ipArray = explode(',', $ipString);
            //remove white spaces
            foreach ($ipArray as $key => $ip) {
                $ipArray[$key] = preg_replace('/\s+/', '', $ip);
            }

            //ip address
            $ipAddress = Mage::helper('core/http')->getRemoteAddr();

            if (in_array($ipAddress, $ipArray)) {
                return true;
            }

            return false;
        } else {
            //empty ip list will ignore the validation
            return true;
        }
    }

    /**
     * Get log file content.
     *
     * @param string $filename
     *
     * @return string
     */
    public function getLogFileContent($filename = 'connector')
    {
        switch ($filename) {
            case "connector":
                $filename = 'connector_api.log';
                break;
            case "system":
                $filename = $this->getWebsiteConfig('dev/log/file');
                break;
            case "exception":
                $filename = $this->getWebsiteConfig('dev/log/exception_file');
                break;
            default:
                return "Log file is not valid. Log file name is " . $filename;
        }

        $pathLogfile = Mage::getBaseDir('var') . DS . 'log' . DS
            . $filename;
        //tail the length file content
        $lengthBefore = 500000;
        //@codingStandardsIgnoreStart
        $handle = fopen($pathLogfile, 'r');
        fseek($handle, -$lengthBefore, SEEK_END);
        if (!$handle) {
            return "Log file is not readable or does not exist at this moment. File path is "
            . $pathLogfile;
        }

        $contents = '';
        if (filesize($pathLogfile) > 0) {
            $contents = fread($handle, filesize($pathLogfile));

            if ($contents === false) {
                return "Log file is not readable or does not exist at this moment. File path is "
                    . $pathLogfile;
            }
            fclose($handle);
        }
        //@codingStandardsIgnoreEnd

        return $contents;
    }


    /**
     * PRODUCT REVIEW REMINDER.
     */
    public function isReviewReminderEnabled($website)
    {
        return $this->getWebsiteConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_REVIEWS_ENABLED,
            $website
        );
    }

    /**
     * @param $website
     *
     * @return string
     */
    public function getReviewReminderOrderStatus($website)
    {
        return $this->getWebsiteConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_AUTOMATION_REVIEW_STATUS,
            $website
        );
    }

    /**
     * @param $website
     *
     * @return int
     */
    public function getReviewReminderDelay($website)
    {
        return $this->getWebsiteConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_AUTOMATION_REVIEW_DELAY,
            $website
        );
    }

    /**
     * @param $website
     *
     * @return int
     */
    public function getReviewReminderCampaign($website)
    {
        return $this->getWebsiteConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_AUTOMATION_REVIEW_CAMPAIGN,
            $website
        );
    }

    /**
     * @param $website
     *
     * @return string
     */
    public function getReviewReminderAnchor($website)
    {
        return $this->getWebsiteConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_AUTOMATION_REVIEW_ANCHOR,
            $website
        );
    }

    /**
     * @param Mage_Core_Model_Website $website
     * @param Mage_Catalog_Model_Product $item
     *
     * @return string
     */
    public function getReviewProductUrl($website, $item)
    {
        if ($this->useProductPage($website)) {
            $url = Mage::getSingleton('ddg_automation/catalog_urlfinder')
                ->fetchFor(
                    $item
                );
        } else {
            $url = Mage::getUrl('review/product/list', array('id'=> $item->getId()));
        }

        return $url.$this->getReviewReminderAnchor($website);
    }

    /**
     * @param $website
     *
     * @return boolean
     */
    protected function useProductPage($website)
    {
        return $this->getWebsiteConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_AUTOMATION_USE_PRODUCT_PAGE,
            $website
        );
    }

    /**
     * @return array
     */
    public function getDynamicStyles()
    {
        return $dynamicStyle = array(
            'nameStyle' => explode(
                ',', Mage::getStoreConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_DYNAMIC_NAME_STYLE)
            ),
            'priceStyle' => explode(
                ',', Mage::getStoreConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_DYNAMIC_PRICE_STYLE)
            ),
            'linkStyle' => explode(
                ',', Mage::getStoreConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_DYNAMIC_LINK_STYLE)
            ),
            'otherStyle' => explode(
                ',', Mage::getStoreConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_DYNAMIC_OTHER_STYLE)
            ),
            'nameColor' => Mage::getStoreConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_DYNAMIC_NAME_COLOR
            ),
            'fontSize' => Mage::getStoreConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_DYNAMIC_NAME_FONT_SIZE
            ),
            'priceColor' => Mage::getStoreConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_DYNAMIC_PRICE_COLOR
            ),
            'priceFontSize' => Mage::getStoreConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_DYNAMIC_PRICE_FONT_SIZE
            ),
            'urlColor' => Mage::getStoreConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_DYNAMIC_LINK_COLOR
            ),
            'urlFontSize' => Mage::getStoreConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_DYNAMIC_LINK_FONT_SIZE
            ),
            'otherColor' => Mage::getStoreConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_DYNAMIC_OTHER_COLOR
            ),
            'otherFontSize' => Mage::getStoreConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_DYNAMIC_OTHER_FONT_SIZE
            ),
            'docFont' => Mage::getStoreConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_DYNAMIC_DOC_FONT
            ),
            'docBackgroundColor' => Mage::getStoreConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_DYNAMIC_DOC_BG_COLOR
            ),
            'dynamicStyling' => Mage::getStoreConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_DYNAMIC_STYLING
            )
        );
    }

    /**
     * Save api credentials.
     *
     * @param $apiUser
     * @param $apiPass
     * @return bool
     */
    public function saveApiCreds($apiUser, $apiPass)
    {
        try {
            $apiPass = Mage::helper('core')->encrypt($apiPass);
            //@codingStandardsIgnoreStart
            $config = new Mage_Core_Model_Config();
            //@codingStandardsIgnoreEnd
            $config->saveConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_API_ENABLED, '1');
            $config->saveConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_API_USERNAME, $apiUser);
            $config->saveConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_API_PASSWORD, $apiPass);
            Mage::getConfig()->cleanCache();

            return true;
        } catch (Exception $e) {
            Mage::logException($e);

            return false;
        }
    }

    /**
     * Setup data fields.
     *
     * @return bool
     */
    public function setupDataFields()
    {
        $error = false;
        $apiModel = false;

        if ($this->isEnabled()) {
            $apiModel = $this->getWebsiteApiClient();
        }

        if (!$apiModel) {
            $error = true;
            $this->log('setupDataFields client is not enabled');
        } else {
            //validate account
            $accountInfo = $apiModel->getAccountInfo();
            if (isset($accountInfo->message)) {
                $this->log('setupDataFields ' . $accountInfo->message);
                $error = true;
            } else {
                $dataFields = Mage::getModel('ddg_automation/connector_datafield')->getContactDatafields();
                foreach ($dataFields as $key => $dataField) {
                    $apiModel->postDataFields($dataField);
                    try {
                        //@codingStandardsIgnoreStart
                        //map the successfully created data field
                        $config = new Mage_Core_Model_Config();
                        $config->saveConfig('connector_data_mapping/customer_data/' . $key,
                            strtoupper($dataField['name']));
                        //@codingStandardsIgnoreEnd
                        Mage::helper('ddg')->log('successfully connected : ' . $dataField['name']);
                    } catch (Exception $e) {
                        Mage::logException($e);
                        $error = true;
                    }
                }
            }
        }

        return $error == true ? false : true;
    }

    /**
     * Create certain address books.
     *
     * @return bool
     */
    public function createAddressBooks()
    {
        $addressBooks = array(
            array('name' => 'Magento_Customers', 'visibility' => 'Private'),
            array('name' => 'Magento_Subscribers', 'visibility' => 'Private'),
            array('name' => 'Magento_Guests', 'visibility' => 'Private'),
        );
        $error = false;
        $client = false;

        if ($this->isEnabled()) {
            $client = $this->getWebsiteApiClient();
        }

        if (!$client) {
            $error = true;
            $this->log('createAddressBooks client is not enabled');
        } else {
            //validate account
            $accountInfo = $client->getAccountInfo();
            if (isset($accountInfo->message)) {
                $this->log('createAddressBooks ' . $accountInfo->message);
                $error = true;
            } else {
                foreach ($addressBooks as $addressBook) {
                    $addressBookName = $addressBook['name'];
                    $visibility = $addressBook['visibility'];
                    if (!empty($addressBookName)) {
                        $response = $client->postAddressBooks($addressBookName, $visibility);
                        if (isset($response->id)) {
                            $this->mapAddressBook($addressBookName, $response->id);
                        } else {
                            $response = $client->getAddressBooks();
                            if (!isset($response->message)) {
                                foreach ($response as $book) {
                                    if ($book->name == $addressBookName) {
                                        $this->mapAddressBook($addressBookName, $book->id);
                                        break;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $error == true ? false : true;
    }

    /**
     * Map the successfully created address book
     *
     * @param $name
     * @param $id
     */
    public function mapAddressBook($name, $id)
    {
        //@codingStandardsIgnoreStart
        $config = new Mage_Core_Model_Config();
        //@codingStandardsIgnoreEnd

        $addressBookMap = array(
            'Magento_Customers' => Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOMERS_ADDRESS_BOOK_ID,
            'Magento_Subscribers'
            => Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SUBSCRIBERS_ADDRESS_BOOK_ID,
            'Magento_Guests' => Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_GUEST_ADDRESS_BOOK_ID
        );

        try {
            //map the successfully created address book
            $config->saveConfig($addressBookMap[$name], $id);
            Mage::helper('ddg')->log('successfully connected address book : ' . $name);
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * Enable certain syncs for newly created trial account.
     *
     * @return bool
     */
    public function enableSyncForTrial()
    {
        try {
            //@codingStandardsIgnoreStart
            $config = new Mage_Core_Model_Config();
            //@codingStandardsIgnoreEnd
            $config->saveConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_CONTACT_ENABLED, '1');
            $config->saveConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_GUEST_ENABLED, '1');
            $config->saveConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_SUBSCRIBER_ENABLED, '1');
            $config->saveConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_ORDER_ENABLED, '1');
            Mage::getConfig()->cleanCache();

            return true;
        } catch (Exception $e) {
            Mage::logException($e);
            return false;
        }
    }

    /**
     * Check if both frontend and backend secure (HTTPS).
     *
     * @return bool
     */
    public function isFrontendAdminSecure()
    {
        $frontend = Mage::app()->getStore()->isFrontUrlSecure();
        $admin = Mage::app()->getStore()->isAdminUrlSecure();
        $current = Mage::app()->getStore()->isCurrentlySecure();

        if ($frontend && $admin && $current) {
            return true;
        }

        return false;
    }

    /**
     * Get difference between dates
     *
     * @param $created
     * @return false|int
     */
    public function getDateDifference($created)
    {
        $now = Mage::getSingleton('core/date')->gmtDate();
        return strtotime($now) - strtotime($created);
    }


    /**
     * Determines if Roi Tracking is Available
     * @return bool
     */
    public function isRoiAvailable()
    {
        if ($this->isEnabled() && $this->getRoiTrackingEnabled()) {
            return true;
        }
        return false;
    }

    /**
     * Determines if Page Tracking is available or no
     * @return bool
     */
    public function isPageTrackingAvailable()
    {
        if ($this->isEnabled() && $this->getPageTrackingEnabled()) {
            return true;
        }
        return false;
    }
}
