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
     * Get the contact id for the custoemer based on website id.
     *
     * @param $email
     * @param $websiteId
     *
     * @return bool
     */
    public function getContactId($email, $websiteId)
    {
        $contact = Mage::getModel('ddg_automation/contact')
            ->loadByCustomerEmail($email, $websiteId);
        if ($contactId = $contact->getContactId()) {
            return $contactId;
        }

        $client = $this->getWebsiteApiClient($websiteId);
        if ($client === false) {
            return false;
        }

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
        }

        return $response->id;
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

        if (! $attr) {
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

        if (! $apiUsername = $this->getApiUsername($website)
            || !$apiPassword = $this->getApiPassword($website)
        ) {
            return false;
        }

        $client = Mage::getModel('ddg_automation/apiconnector_client');
        $client->setApiUsername($this->getApiUsername($website))
            ->setApiPassword($this->getApiPassword($website));

        return $client;
    }

    /**
     * Retrieve authorisation code.
     */
    public function getCode()
    {
        $adminUser = Mage::getSingleton('admin/session')->getUser();
        $code = $adminUser->getEmailCode();

        return $code;
    }

    /**
     * Autorisation url for OAUTH.
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

        if (! empty($data)) {
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
            //update datafields for conctact
            $client->updateContactDatafieldsByEmail($email, $data);
        }
    }

    /**
     * @param int $websiteId
     *
     * @return bool
     */
    public function getOrderSyncEnabled($websiteId = 0)
    {
        return Mage::getStoreConfigFlag(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_ORDER_ENABLED,
            $websiteId
        );
    }

    /**
     * @param int $websiteId
     *
     * @return bool
     */
    public function getCatalogSyncEnabled($websiteId = 0)
    {
        return Mage::getStoreConfigFlag(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_CATALOG_ENABLED,
            $websiteId
        );
    }

    /**
     * @param int $websiteId
     *
     * @return bool
     */
    public function getContactSyncEnabled($websiteId = 0)
    {
        return Mage::getStoreConfigFlag(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_CONTACT_ENABLED,
            $websiteId
        );
    }

    /**
     * @param int $websiteId
     *
     * @return bool
     */
    public function getGuestSyncEnabled($websiteId = 0)
    {
        return Mage::getStoreConfigFlag(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_GUEST_ENABLED,
            $websiteId
        );
    }

    /**
     * @param int $websiteId
     *
     * @return bool
     */
    public function getSubscriberSyncEnabled($websiteId = 0)
    {
        return Mage::getStoreConfigFlag(
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
        $website = Mage::app()->getWebsite($websiteId);
        $limit = $website->getConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_DEBUG_API_REQUEST_LIMIT
        );

        return $limit;
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
        if (! $handle) {
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
     * Generate url for iframe for trial account popup.
     *
     * @return string
     * @throws Mage_Core_Exception
     */
    public function getIframeFormUrl()
    {
        $formUrl = Dotdigitalgroup_Email_Helper_Config::API_CONNECTOR_TRIAL_FORM_URL;
        $ipAddress = Mage::helper('core/http')->getRemoteAddr();
        $timezone = $this->getTimeZoneId();
        $culture = $this->getCultureId();
        $company = Mage::app()->getWebsite()->getConfig('general/store_information/name');
        $callback = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB, true) . 'connector/trial/accountcallback';
        //query params
        $params = array(
            'callback' => $callback,
            'company' => $company,
            'culture' => $culture,
            'timezone' => $timezone,
            'ip' => $ipAddress
        );
        $url = $formUrl . '?' . http_build_query($params);

        return $url;
    }

    /**
     * Get time zone id for trial account.
     *
     * @return string
     * @throws Mage_Core_Exception
     */
    public function getTimeZoneId()
    {
        $timeZone = Mage::app()->getWebsite()->getConfig(Mage_Core_Model_Locale::XML_PATH_DEFAULT_TIMEZONE);
        $result = '085';
        if ($timeZone) {
            $timeZones = Array
            (
                Array("MageTimeZone" => "Australia/Darwin", "MicrosoftTimeZoneIndex" => "250"),
                Array("MageTimeZone" => "Australia/Melbourne", "MicrosoftTimeZoneIndex" => "260"),
                Array("MageTimeZone" => "Australia/Sydney", "MicrosoftTimeZoneIndex" => "260"),
                Array("MageTimeZone" => "Asia/Kabul", "MicrosoftTimeZoneIndex" => "175"),
                Array("MageTimeZone" => "America/Anchorage", "MicrosoftTimeZoneIndex" => "003"),
                Array("MageTimeZone" => "America/Juneau", "MicrosoftTimeZoneIndex" => "003"),
                Array("MageTimeZone" => "America/Nome", "MicrosoftTimeZoneIndex" => "003"),
                Array("MageTimeZone" => "America/Sitka", "MicrosoftTimeZoneIndex" => "003"),
                Array("MageTimeZone" => "America/Yakutat", "MicrosoftTimeZoneIndex" => "003"),
                Array("MageTimeZone" => "Asia/Aden", "MicrosoftTimeZoneIndex" => "150"),
                Array("MageTimeZone" => "Asia/Bahrain", "MicrosoftTimeZoneIndex" => "150"),
                Array("MageTimeZone" => "Asia/Kuwait", "MicrosoftTimeZoneIndex" => "150"),
                Array("MageTimeZone" => "Asia/Qatar", "MicrosoftTimeZoneIndex" => "150"),
                Array("MageTimeZone" => "Asia/Riyadh", "MicrosoftTimeZoneIndex" => "150"),
                Array("MageTimeZone" => "Asia/Dubai", "MicrosoftTimeZoneIndex" => "165"),
                Array("MageTimeZone" => "Asia/Muscat", "MicrosoftTimeZoneIndex" => "165"),
                Array("MageTimeZone" => "Etc/GMT-4", "MicrosoftTimeZoneIndex" => "165"),
                Array("MageTimeZone" => "Asia/Baghdad", "MicrosoftTimeZoneIndex" => "165"),
                Array("MageTimeZone" => "America/Argentina/La_Rioja", "MicrosoftTimeZoneIndex" => "070"),
                Array("MageTimeZone" => "America/Argentina/Rio_Gallegos", "MicrosoftTimeZoneIndex" => "070"),
                Array("MageTimeZone" => "America/Argentina/Salta", "MicrosoftTimeZoneIndex" => "070"),
                Array("MageTimeZone" => "America/Argentina/San_Juan", "MicrosoftTimeZoneIndex" => "070"),
                Array("MageTimeZone" => "America/Argentina/San_Luis", "MicrosoftTimeZoneIndex" => "070"),
                Array("MageTimeZone" => "America/Argentina/Tucuman", "MicrosoftTimeZoneIndex" => "070"),
                Array("MageTimeZone" => "America/Argentina/Ushuaia", "MicrosoftTimeZoneIndex" => "070"),
                Array("MageTimeZone" => "America/Buenos_Aires", "MicrosoftTimeZoneIndex" => "070"),
                Array("MageTimeZone" => "America/Catamarca", "MicrosoftTimeZoneIndex" => "070"),
                Array("MageTimeZone" => "America/Cordoba", "MicrosoftTimeZoneIndex" => "070"),
                Array("MageTimeZone" => "America/Jujuy", "MicrosoftTimeZoneIndex" => "070"),
                Array("MageTimeZone" => "America/Mendoza", "MicrosoftTimeZoneIndex" => "070"),
                Array("MageTimeZone" => "America/Glace_Bay", "MicrosoftTimeZoneIndex" => "050"),
                Array("MageTimeZone" => "America/Goose_Bay", "MicrosoftTimeZoneIndex" => "050"),
                Array("MageTimeZone" => "America/Halifax", "MicrosoftTimeZoneIndex" => "050"),
                Array("MageTimeZone" => "America/Moncton", "MicrosoftTimeZoneIndex" => "050"),
                Array("MageTimeZone" => "America/Thule", "MicrosoftTimeZoneIndex" => "050"),
                Array("MageTimeZone" => "Atlantic/Bermuda", "MicrosoftTimeZoneIndex" => "050"),
                Array("MageTimeZone" => "Asia/Baku", "MicrosoftTimeZoneIndex" => "170"),
                Array("MageTimeZone" => "America/Scoresbysund", "MicrosoftTimeZoneIndex" => "080"),
                Array("MageTimeZone" => "Atlantic/Azores", "MicrosoftTimeZoneIndex" => "080"),
                Array("MageTimeZone" => "America/Bahia", "MicrosoftTimeZoneIndex" => "065"),
                Array("MageTimeZone" => "Asia/Dhaka", "MicrosoftTimeZoneIndex" => "195"),
                Array("MageTimeZone" => "Asia/Thimphu", "MicrosoftTimeZoneIndex" => "195"),
                Array("MageTimeZone" => "America/Regina", "MicrosoftTimeZoneIndex" => "025"),
                Array("MageTimeZone" => "America/Swift_Current", "MicrosoftTimeZoneIndex" => "025"),
                Array("MageTimeZone" => "Atlantic/Cape_Verde", "MicrosoftTimeZoneIndex" => "083"),
                Array("MageTimeZone" => "Etc/GMT+1", "MicrosoftTimeZoneIndex" => "083"),
                Array("MageTimeZone" => "Asia/Yerevan", "MicrosoftTimeZoneIndex" => "170"),
                Array("MageTimeZone" => "Australia/Adelaide", "MicrosoftTimeZoneIndex" => "250"),
                Array("MageTimeZone" => "Australia/Broken_Hill", "MicrosoftTimeZoneIndex" => "250"),
                Array("MageTimeZone" => "America/Belize", "MicrosoftTimeZoneIndex" => "033"),
                Array("MageTimeZone" => "America/Costa_Rica", "MicrosoftTimeZoneIndex" => "033"),
                Array("MageTimeZone" => "America/El_Salvador", "MicrosoftTimeZoneIndex" => "033"),
                Array("MageTimeZone" => "America/Guatemala", "MicrosoftTimeZoneIndex" => "033"),
                Array("MageTimeZone" => "America/Managua", "MicrosoftTimeZoneIndex" => "033"),
                Array("MageTimeZone" => "America/Tegucigalpa", "MicrosoftTimeZoneIndex" => "033"),
                Array("MageTimeZone" => "Etc/GMT+6", "MicrosoftTimeZoneIndex" => "033"),
                Array("MageTimeZone" => "Pacific/Galapagos", "MicrosoftTimeZoneIndex" => "033"),
                Array("MageTimeZone" => "Antarctica/Vostok", "MicrosoftTimeZoneIndex" => "195"),
                Array("MageTimeZone" => "Asia/Almaty", "MicrosoftTimeZoneIndex" => "195"),
                Array("MageTimeZone" => "Asia/Bishkek", "MicrosoftTimeZoneIndex" => "195"),
                Array("MageTimeZone" => "Asia/Qyzylorda", "MicrosoftTimeZoneIndex" => "195"),
                Array("MageTimeZone" => "Etc/GMT-6", "MicrosoftTimeZoneIndex" => "195"),
                Array("MageTimeZone" => "Indian/Chagos", "MicrosoftTimeZoneIndex" => "195"),
                Array("MageTimeZone" => "America/Campo_Grande", "MicrosoftTimeZoneIndex" => "065"),
                Array("MageTimeZone" => "America/Cuiaba", "MicrosoftTimeZoneIndex" => "065"),
                Array("MageTimeZone" => "Europe/Belgrade", "MicrosoftTimeZoneIndex" => "095"),
                Array("MageTimeZone" => "Europe/Bratislava", "MicrosoftTimeZoneIndex" => "095"),
                Array("MageTimeZone" => "Europe/Budapest", "MicrosoftTimeZoneIndex" => "095"),
                Array("MageTimeZone" => "Europe/Ljubljana", "MicrosoftTimeZoneIndex" => "095"),
                Array("MageTimeZone" => "Europe/Podgorica", "MicrosoftTimeZoneIndex" => "095"),
                Array("MageTimeZone" => "Europe/Prague", "MicrosoftTimeZoneIndex" => "095"),
                Array("MageTimeZone" => "Europe/Tirane", "MicrosoftTimeZoneIndex" => "095"),
                Array("MageTimeZone" => "Europe/Sarajevo", "MicrosoftTimeZoneIndex" => "095"),
                Array("MageTimeZone" => "Europe/Skopje", "MicrosoftTimeZoneIndex" => "095"),
                Array("MageTimeZone" => "Europe/Warsaw", "MicrosoftTimeZoneIndex" => "095"),
                Array("MageTimeZone" => "Europe/Zagreb", "MicrosoftTimeZoneIndex" => "095"),
                Array("MageTimeZone" => "Antarctica/Macquarie", "MicrosoftTimeZoneIndex" => "280"),
                Array("MageTimeZone" => "Etc/GMT-11", "MicrosoftTimeZoneIndex" => "280"),
                Array("MageTimeZone" => "Pacific/Efate", "MicrosoftTimeZoneIndex" => "280"),
                Array("MageTimeZone" => "Pacific/Guadalcanal", "MicrosoftTimeZoneIndex" => "280"),
                Array("MageTimeZone" => "Pacific/Kosrae", "MicrosoftTimeZoneIndex" => "280"),
                Array("MageTimeZone" => "Pacific/Noumea", "MicrosoftTimeZoneIndex" => "280"),
                Array("MageTimeZone" => "Pacific/Ponape", "MicrosoftTimeZoneIndex" => "280"),
                Array("MageTimeZone" => "America/Chicago", "MicrosoftTimeZoneIndex" => "020"),
                Array("MageTimeZone" => "America/Indiana/Knox", "MicrosoftTimeZoneIndex" => "020"),
                Array("MageTimeZone" => "America/Indiana/Tell_City", "MicrosoftTimeZoneIndex" => "020"),
                Array("MageTimeZone" => "America/Matamoros", "MicrosoftTimeZoneIndex" => "020"),
                Array("MageTimeZone" => "America/Menominee", "MicrosoftTimeZoneIndex" => "020"),
                Array("MageTimeZone" => "America/North_Dakota/Beulah", "MicrosoftTimeZoneIndex" => "020"),
                Array("MageTimeZone" => "America/North_Dakota/Center", "MicrosoftTimeZoneIndex" => "020"),
                Array("MageTimeZone" => "America/North_Dakota/New_Salem", "MicrosoftTimeZoneIndex" => "020"),
                Array("MageTimeZone" => "America/Rainy_River", "MicrosoftTimeZoneIndex" => "020"),
                Array("MageTimeZone" => "America/Rankin_Inlet", "MicrosoftTimeZoneIndex" => "020"),
                Array("MageTimeZone" => "America/Resolute", "MicrosoftTimeZoneIndex" => "020"),
                Array("MageTimeZone" => "America/Winnipeg", "MicrosoftTimeZoneIndex" => "020"),
                Array("MageTimeZone" => "CST6CDT", "MicrosoftTimeZoneIndex" => "020"),
                Array("MageTimeZone" => "America/Bahia_Banderas", "MicrosoftTimeZoneIndex" => "020"),
                Array("MageTimeZone" => "America/Cancun", "MicrosoftTimeZoneIndex" => "020"),
                Array("MageTimeZone" => "America/Merida", "MicrosoftTimeZoneIndex" => "020"),
                Array("MageTimeZone" => "America/Mexico_City", "MicrosoftTimeZoneIndex" => "020"),
                Array("MageTimeZone" => "America/Monterrey", "MicrosoftTimeZoneIndex" => "020"),
                Array("MageTimeZone" => "Asia/Chongqing", "MicrosoftTimeZoneIndex" => "210"),
                Array("MageTimeZone" => "Asia/Harbin", "MicrosoftTimeZoneIndex" => "210"),
                Array("MageTimeZone" => "Asia/Hong_Kong", "MicrosoftTimeZoneIndex" => "210"),
                Array("MageTimeZone" => "Asia/Kashgar", "MicrosoftTimeZoneIndex" => "210"),
                Array("MageTimeZone" => "Asia/Macau", "MicrosoftTimeZoneIndex" => "210"),
                Array("MageTimeZone" => "Asia/Shanghai", "MicrosoftTimeZoneIndex" => "210"),
                Array("MageTimeZone" => "Asia/Urumqi", "MicrosoftTimeZoneIndex" => "210"),
                Array("MageTimeZone" => "Etc/GMT+12", "MicrosoftTimeZoneIndex" => "000"),
                Array("MageTimeZone" => "Africa/Addis_Ababa", "MicrosoftTimeZoneIndex" => "115"),
                Array("MageTimeZone" => "Africa/Asmera", "MicrosoftTimeZoneIndex" => "115"),
                Array("MageTimeZone" => "Africa/Dar_es_Salaam", "MicrosoftTimeZoneIndex" => "115"),
                Array("MageTimeZone" => "Africa/Djibouti", "MicrosoftTimeZoneIndex" => "115"),
                Array("MageTimeZone" => "Africa/Juba", "MicrosoftTimeZoneIndex" => "115"),
                Array("MageTimeZone" => "Africa/Kampala", "MicrosoftTimeZoneIndex" => "115"),
                Array("MageTimeZone" => "Africa/Khartoum", "MicrosoftTimeZoneIndex" => "115"),
                Array("MageTimeZone" => "Africa/Mogadishu", "MicrosoftTimeZoneIndex" => "115"),
                Array("MageTimeZone" => "Africa/Nairobi", "MicrosoftTimeZoneIndex" => "115"),
                Array("MageTimeZone" => "Antarctica/Syowa", "MicrosoftTimeZoneIndex" => "115"),
                Array("MageTimeZone" => "Etc/GMT-3", "MicrosoftTimeZoneIndex" => "115"),
                Array("MageTimeZone" => "Indian/Antananarivo", "MicrosoftTimeZoneIndex" => "115"),
                Array("MageTimeZone" => "Indian/Comoro", "MicrosoftTimeZoneIndex" => "115"),
                Array("MageTimeZone" => "Indian/Mayotte", "MicrosoftTimeZoneIndex" => "115"),
                Array("MageTimeZone" => "Australia/Brisbane", "MicrosoftTimeZoneIndex" => "260"),
                Array("MageTimeZone" => "Australia/Lindeman", "MicrosoftTimeZoneIndex" => "260"),
                Array("MageTimeZone" => "America/Sao_Paulo", "MicrosoftTimeZoneIndex" => "065"),
                Array("MageTimeZone" => "America/Detroit", "MicrosoftTimeZoneIndex" => "035"),
                Array("MageTimeZone" => "America/Grand_Turk", "MicrosoftTimeZoneIndex" => "035"),
                Array("MageTimeZone" => "America/Havana", "MicrosoftTimeZoneIndex" => "035"),
                Array("MageTimeZone" => "America/Indiana/Petersburg", "MicrosoftTimeZoneIndex" => "035"),
                Array("MageTimeZone" => "America/Indiana/Vincennes", "MicrosoftTimeZoneIndex" => "035"),
                Array("MageTimeZone" => "America/Indiana/Winamac", "MicrosoftTimeZoneIndex" => "035"),
                Array("MageTimeZone" => "America/Iqaluit", "MicrosoftTimeZoneIndex" => "035"),
                Array("MageTimeZone" => "America/Kentucky/Monticello", "MicrosoftTimeZoneIndex" => "035"),
                Array("MageTimeZone" => "America/Louisville", "MicrosoftTimeZoneIndex" => "035"),
                Array("MageTimeZone" => "America/Montreal", "MicrosoftTimeZoneIndex" => "035"),
                Array("MageTimeZone" => "America/Nassau", "MicrosoftTimeZoneIndex" => "035"),
                Array("MageTimeZone" => "America/New_York", "MicrosoftTimeZoneIndex" => "035"),
                Array("MageTimeZone" => "America/Nipigon", "MicrosoftTimeZoneIndex" => "035"),
                Array("MageTimeZone" => "America/Pangnirtung", "MicrosoftTimeZoneIndex" => "035"),
                Array("MageTimeZone" => "America/Port-au-Prince", "MicrosoftTimeZoneIndex" => "035"),
                Array("MageTimeZone" => "America/Thunder_Bay", "MicrosoftTimeZoneIndex" => "035"),
                Array("MageTimeZone" => "America/Toronto", "MicrosoftTimeZoneIndex" => "035"),
                Array("MageTimeZone" => "EST5EDT", "MicrosoftTimeZoneIndex" => "035"),
                Array("MageTimeZone" => "Africa/Cairo", "MicrosoftTimeZoneIndex" => "120"),
                Array("MageTimeZone" => "Asia/Yekaterinburg", "MicrosoftTimeZoneIndex" => "180"),
                Array("MageTimeZone" => "Europe/Helsinki", "MicrosoftTimeZoneIndex" => "125"),
                Array("MageTimeZone" => "Europe/Kiev", "MicrosoftTimeZoneIndex" => "125"),
                Array("MageTimeZone" => "Europe/Riga", "MicrosoftTimeZoneIndex" => "125"),
                Array("MageTimeZone" => "Europe/Simferopol", "MicrosoftTimeZoneIndex" => "125"),
                Array("MageTimeZone" => "Europe/Sofia", "MicrosoftTimeZoneIndex" => "125"),
                Array("MageTimeZone" => "Europe/Tallinn", "MicrosoftTimeZoneIndex" => "125"),
                Array("MageTimeZone" => "Europe/Uzhgorod", "MicrosoftTimeZoneIndex" => "125"),
                Array("MageTimeZone" => "Europe/Vilnius", "MicrosoftTimeZoneIndex" => "125"),
                Array("MageTimeZone" => "Europe/Zaporozhye", "MicrosoftTimeZoneIndex" => "125"),
                Array("MageTimeZone" => "Pacific/Fiji", "MicrosoftTimeZoneIndex" => "285"),
                Array("MageTimeZone" => "Atlantic/Canary", "MicrosoftTimeZoneIndex" => "085"),
                Array("MageTimeZone" => "Atlantic/Faeroe", "MicrosoftTimeZoneIndex" => "085"),
                Array("MageTimeZone" => "Atlantic/Madeira", "MicrosoftTimeZoneIndex" => "085"),
                Array("MageTimeZone" => "Europe/Dublin", "MicrosoftTimeZoneIndex" => "085"),
                Array("MageTimeZone" => "Europe/Guernsey", "MicrosoftTimeZoneIndex" => "085"),
                Array("MageTimeZone" => "Europe/Isle_of_Man", "MicrosoftTimeZoneIndex" => "085"),
                Array("MageTimeZone" => "Europe/Jersey", "MicrosoftTimeZoneIndex" => "085"),
                Array("MageTimeZone" => "Europe/Lisbon", "MicrosoftTimeZoneIndex" => "085"),
                Array("MageTimeZone" => "Europe/London", "MicrosoftTimeZoneIndex" => "085"),
                Array("MageTimeZone" => "Asia/Nicosia", "MicrosoftTimeZoneIndex" => "130"),
                Array("MageTimeZone" => "Europe/Athens", "MicrosoftTimeZoneIndex" => "130"),
                Array("MageTimeZone" => "Europe/Bucharest", "MicrosoftTimeZoneIndex" => "130"),
                Array("MageTimeZone" => "Europe/Chisinau", "MicrosoftTimeZoneIndex" => "130"),
                Array("MageTimeZone" => "Asia/Tbilisi", "MicrosoftTimeZoneIndex" => "170"),
                Array("MageTimeZone" => "America/Godthab", "MicrosoftTimeZoneIndex" => "073"),
                Array("MageTimeZone" => "Africa/Abidjan", "MicrosoftTimeZoneIndex" => "090"),
                Array("MageTimeZone" => "Africa/Accra", "MicrosoftTimeZoneIndex" => "090"),
                Array("MageTimeZone" => "Africa/Bamako", "MicrosoftTimeZoneIndex" => "090"),
                Array("MageTimeZone" => "Africa/Banjul", "MicrosoftTimeZoneIndex" => "090"),
                Array("MageTimeZone" => "Africa/Bissau", "MicrosoftTimeZoneIndex" => "090"),
                Array("MageTimeZone" => "Africa/Conakry", "MicrosoftTimeZoneIndex" => "090"),
                Array("MageTimeZone" => "Africa/Dakar", "MicrosoftTimeZoneIndex" => "090"),
                Array("MageTimeZone" => "Africa/Freetown", "MicrosoftTimeZoneIndex" => "090"),
                Array("MageTimeZone" => "Africa/Lome", "MicrosoftTimeZoneIndex" => "090"),
                Array("MageTimeZone" => "Africa/Monrovia", "MicrosoftTimeZoneIndex" => "090"),
                Array("MageTimeZone" => "Africa/Nouakchott", "MicrosoftTimeZoneIndex" => "090"),
                Array("MageTimeZone" => "Africa/Ouagadougou", "MicrosoftTimeZoneIndex" => "090"),
                Array("MageTimeZone" => "Africa/Sao_Tome", "MicrosoftTimeZoneIndex" => "090"),
                Array("MageTimeZone" => "Atlantic/Reykjavik", "MicrosoftTimeZoneIndex" => "090"),
                Array("MageTimeZone" => "Atlantic/St_Helena", "MicrosoftTimeZoneIndex" => "090"),
                Array("MageTimeZone" => "Etc/GMT+10", "MicrosoftTimeZoneIndex" => "002"),
                Array("MageTimeZone" => "Pacific/Honolulu", "MicrosoftTimeZoneIndex" => "002"),
                Array("MageTimeZone" => "Pacific/Johnston", "MicrosoftTimeZoneIndex" => "002"),
                Array("MageTimeZone" => "Pacific/Rarotonga", "MicrosoftTimeZoneIndex" => "002"),
                Array("MageTimeZone" => "Pacific/Tahiti", "MicrosoftTimeZoneIndex" => "002"),
                Array("MageTimeZone" => "Asia/Calcutta", "MicrosoftTimeZoneIndex" => "190"),
                Array("MageTimeZone" => "Asia/Tehran", "MicrosoftTimeZoneIndex" => "160"),
                Array("MageTimeZone" => "Asia/Jerusalem", "MicrosoftTimeZoneIndex" => "135"),
                Array("MageTimeZone" => "Asia/Amman", "MicrosoftTimeZoneIndex" => "150"),
                Array("MageTimeZone" => "Europe/Kaliningrad", "MicrosoftTimeZoneIndex" => "130"),
                Array("MageTimeZone" => "Europe/Minsk", "MicrosoftTimeZoneIndex" => "130"),
                Array("MageTimeZone" => "Asia/Pyongyang", "MicrosoftTimeZoneIndex" => "230"),
                Array("MageTimeZone" => "Asia/Seoul", "MicrosoftTimeZoneIndex" => "230"),
                Array("MageTimeZone" => "Africa/Tripoli", "MicrosoftTimeZoneIndex" => "120"),
                Array("MageTimeZone" => "Asia/Anadyr", "MicrosoftTimeZoneIndex" => "280"),
                Array("MageTimeZone" => "Asia/Kamchatka", "MicrosoftTimeZoneIndex" => "280"),
                Array("MageTimeZone" => "Asia/Magadan", "MicrosoftTimeZoneIndex" => "280"),
                Array("MageTimeZone" => "Indian/Mahe", "MicrosoftTimeZoneIndex" => "165"),
                Array("MageTimeZone" => "Indian/Mauritius", "MicrosoftTimeZoneIndex" => "165"),
                Array("MageTimeZone" => "Indian/Reunion", "MicrosoftTimeZoneIndex" => "165"),
                Array("MageTimeZone" => "Asia/Beirut", "MicrosoftTimeZoneIndex" => "158"),
                Array("MageTimeZone" => "America/Montevideo", "MicrosoftTimeZoneIndex" => "065"),
                Array("MageTimeZone" => "Africa/Casablanca", "MicrosoftTimeZoneIndex" => "113"),
                Array("MageTimeZone" => "Africa/El_Aaiun", "MicrosoftTimeZoneIndex" => "113"),
                Array("MageTimeZone" => "America/Boise", "MicrosoftTimeZoneIndex" => "010"),
                Array("MageTimeZone" => "America/Cambridge_Bay", "MicrosoftTimeZoneIndex" => "010"),
                Array("MageTimeZone" => "America/Denver", "MicrosoftTimeZoneIndex" => "010"),
                Array("MageTimeZone" => "America/Edmonton", "MicrosoftTimeZoneIndex" => "010"),
                Array("MageTimeZone" => "America/Inuvik", "MicrosoftTimeZoneIndex" => "010"),
                Array("MageTimeZone" => "America/Ojinaga", "MicrosoftTimeZoneIndex" => "010"),
                Array("MageTimeZone" => "America/Shiprock", "MicrosoftTimeZoneIndex" => "010"),
                Array("MageTimeZone" => "America/Yellowknife", "MicrosoftTimeZoneIndex" => "010"),
                Array("MageTimeZone" => "MST7MDT", "MicrosoftTimeZoneIndex" => "010"),
                Array("MageTimeZone" => "America/Chihuahua", "MicrosoftTimeZoneIndex" => "010"),
                Array("MageTimeZone" => "America/Mazatlan", "MicrosoftTimeZoneIndex" => "010"),
                Array("MageTimeZone" => "Asia/Rangoon", "MicrosoftTimeZoneIndex" => "203"),
                Array("MageTimeZone" => "Indian/Cocos", "MicrosoftTimeZoneIndex" => "203"),
                Array("MageTimeZone" => "Asia/Novokuznetsk", "MicrosoftTimeZoneIndex" => "201"),
                Array("MageTimeZone" => "Asia/Novosibirsk", "MicrosoftTimeZoneIndex" => "201"),
                Array("MageTimeZone" => "Asia/Omsk", "MicrosoftTimeZoneIndex" => "201"),
                Array("MageTimeZone" => "Africa/Windhoek", "MicrosoftTimeZoneIndex" => "120"),
                Array("MageTimeZone" => "Asia/Katmandu", "MicrosoftTimeZoneIndex" => "193"),
                Array("MageTimeZone" => "Antarctica/McMurdo", "MicrosoftTimeZoneIndex" => "290"),
                Array("MageTimeZone" => "Antarctica/South_Pole", "MicrosoftTimeZoneIndex" => "290"),
                Array("MageTimeZone" => "Pacific/Auckland", "MicrosoftTimeZoneIndex" => "290"),
                Array("MageTimeZone" => "America/St_Johns", "MicrosoftTimeZoneIndex" => "060"),
                Array("MageTimeZone" => "Asia/Irkutsk", "MicrosoftTimeZoneIndex" => "207"),
                Array("MageTimeZone" => "Asia/Krasnoyarsk", "MicrosoftTimeZoneIndex" => "207"),
                Array("MageTimeZone" => "America/Santiago", "MicrosoftTimeZoneIndex" => "056"),
                Array("MageTimeZone" => "Antarctica/Palmer", "MicrosoftTimeZoneIndex" => "004"),
                Array("MageTimeZone" => "America/Dawson", "MicrosoftTimeZoneIndex" => "004"),
                Array("MageTimeZone" => "America/Los_Angeles", "MicrosoftTimeZoneIndex" => "004"),
                Array("MageTimeZone" => "America/Tijuana", "MicrosoftTimeZoneIndex" => "004"),
                Array("MageTimeZone" => "America/Vancouver", "MicrosoftTimeZoneIndex" => "004"),
                Array("MageTimeZone" => "America/Whitehorse", "MicrosoftTimeZoneIndex" => "004"),
                Array("MageTimeZone" => "America/Santa_Isabel", "MicrosoftTimeZoneIndex" => "004"),
                Array("MageTimeZone" => "PST8PDT", "MicrosoftTimeZoneIndex" => "004"),
                Array("MageTimeZone" => "Asia/Karachi", "MicrosoftTimeZoneIndex" => "185"),
                Array("MageTimeZone" => "America/Asuncion", "MicrosoftTimeZoneIndex" => "065"),
                Array("MageTimeZone" => "Africa/Ceuta", "MicrosoftTimeZoneIndex" => "105"),
                Array("MageTimeZone" => "Europe/Brussels", "MicrosoftTimeZoneIndex" => "105"),
                Array("MageTimeZone" => "Europe/Copenhagen", "MicrosoftTimeZoneIndex" => "105"),
                Array("MageTimeZone" => "Europe/Madrid", "MicrosoftTimeZoneIndex" => "105"),
                Array("MageTimeZone" => "Europe/Paris", "MicrosoftTimeZoneIndex" => "105"),
                Array("MageTimeZone" => "Europe/Moscow", "MicrosoftTimeZoneIndex" => "145"),
                Array("MageTimeZone" => "Europe/Samara", "MicrosoftTimeZoneIndex" => "145"),
                Array("MageTimeZone" => "Europe/Volgograd", "MicrosoftTimeZoneIndex" => "145"),
                Array("MageTimeZone" => "America/Araguaina", "MicrosoftTimeZoneIndex" => "070"),
                Array("MageTimeZone" => "America/Belem", "MicrosoftTimeZoneIndex" => "070"),
                Array("MageTimeZone" => "America/Cayenne", "MicrosoftTimeZoneIndex" => "070"),
                Array("MageTimeZone" => "America/Fortaleza", "MicrosoftTimeZoneIndex" => "070"),
                Array("MageTimeZone" => "America/Maceio", "MicrosoftTimeZoneIndex" => "070"),
                Array("MageTimeZone" => "America/Paramaribo", "MicrosoftTimeZoneIndex" => "070"),
                Array("MageTimeZone" => "America/Recife", "MicrosoftTimeZoneIndex" => "070"),
                Array("MageTimeZone" => "America/Santarem", "MicrosoftTimeZoneIndex" => "070"),
                Array("MageTimeZone" => "Antarctica/Rothera", "MicrosoftTimeZoneIndex" => "070"),
                Array("MageTimeZone" => "Atlantic/Stanley", "MicrosoftTimeZoneIndex" => "070"),
                Array("MageTimeZone" => "Etc/GMT+3", "MicrosoftTimeZoneIndex" => "070"),
                Array("MageTimeZone" => "America/Bogota", "MicrosoftTimeZoneIndex" => "045"),
                Array("MageTimeZone" => "America/Cayman", "MicrosoftTimeZoneIndex" => "045"),
                Array("MageTimeZone" => "America/Coral_Harbour", "MicrosoftTimeZoneIndex" => "045"),
                Array("MageTimeZone" => "America/Eirunepe", "MicrosoftTimeZoneIndex" => "045"),
                Array("MageTimeZone" => "America/Guayaquil", "MicrosoftTimeZoneIndex" => "045"),
                Array("MageTimeZone" => "America/Jamaica", "MicrosoftTimeZoneIndex" => "045"),
                Array("MageTimeZone" => "America/Lima", "MicrosoftTimeZoneIndex" => "045"),
                Array("MageTimeZone" => "America/Panama", "MicrosoftTimeZoneIndex" => "045"),
                Array("MageTimeZone" => "America/Rio_Branco", "MicrosoftTimeZoneIndex" => "045"),
                Array("MageTimeZone" => "Etc/GMT+5", "MicrosoftTimeZoneIndex" => "045"),
                Array("MageTimeZone" => "America/Anguilla", "MicrosoftTimeZoneIndex" => "055"),
                Array("MageTimeZone" => "America/Antigua", "MicrosoftTimeZoneIndex" => "055"),
                Array("MageTimeZone" => "America/Aruba", "MicrosoftTimeZoneIndex" => "055"),
                Array("MageTimeZone" => "America/Barbados", "MicrosoftTimeZoneIndex" => "055"),
                Array("MageTimeZone" => "America/Blanc-Sablon", "MicrosoftTimeZoneIndex" => "055"),
                Array("MageTimeZone" => "America/Boa_Vista", "MicrosoftTimeZoneIndex" => "055"),
                Array("MageTimeZone" => "America/Curacao", "MicrosoftTimeZoneIndex" => "055"),
                Array("MageTimeZone" => "America/Dominica", "MicrosoftTimeZoneIndex" => "055"),
                Array("MageTimeZone" => "America/Grenada", "MicrosoftTimeZoneIndex" => "055"),
                Array("MageTimeZone" => "America/Guadeloupe", "MicrosoftTimeZoneIndex" => "055"),
                Array("MageTimeZone" => "America/Guyana", "MicrosoftTimeZoneIndex" => "055"),
                Array("MageTimeZone" => "America/Kralendijk", "MicrosoftTimeZoneIndex" => "055"),
                Array("MageTimeZone" => "America/La_Paz", "MicrosoftTimeZoneIndex" => "055"),
                Array("MageTimeZone" => "America/Lower_Princes", "MicrosoftTimeZoneIndex" => "055"),
                Array("MageTimeZone" => "America/Manaus", "MicrosoftTimeZoneIndex" => "055"),
                Array("MageTimeZone" => "America/Marigot", "MicrosoftTimeZoneIndex" => "055"),
                Array("MageTimeZone" => "America/Martinique", "MicrosoftTimeZoneIndex" => "055"),
                Array("MageTimeZone" => "America/Montserrat", "MicrosoftTimeZoneIndex" => "055"),
                Array("MageTimeZone" => "America/Port_of_Spain", "MicrosoftTimeZoneIndex" => "055"),
                Array("MageTimeZone" => "America/Porto_Velho", "MicrosoftTimeZoneIndex" => "055"),
                Array("MageTimeZone" => "America/Puerto_Rico", "MicrosoftTimeZoneIndex" => "055"),
                Array("MageTimeZone" => "America/Santo_Domingo", "MicrosoftTimeZoneIndex" => "055"),
                Array("MageTimeZone" => "America/St_Barthelemy", "MicrosoftTimeZoneIndex" => "055"),
                Array("MageTimeZone" => "America/St_Kitts", "MicrosoftTimeZoneIndex" => "055"),
                Array("MageTimeZone" => "America/St_Lucia", "MicrosoftTimeZoneIndex" => "055"),
                Array("MageTimeZone" => "America/St_Thomas", "MicrosoftTimeZoneIndex" => "055"),
                Array("MageTimeZone" => "America/St_Vincent", "MicrosoftTimeZoneIndex" => "055"),
                Array("MageTimeZone" => "America/Tortola", "MicrosoftTimeZoneIndex" => "055"),
                Array("MageTimeZone" => "Etc/GMT+4", "MicrosoftTimeZoneIndex" => "055"),
                Array("MageTimeZone" => "Antarctica/Davis", "MicrosoftTimeZoneIndex" => "205"),
                Array("MageTimeZone" => "Asia/Bangkok", "MicrosoftTimeZoneIndex" => "205"),
                Array("MageTimeZone" => "Asia/Hovd", "MicrosoftTimeZoneIndex" => "205"),
                Array("MageTimeZone" => "Asia/Jakarta", "MicrosoftTimeZoneIndex" => "205"),
                Array("MageTimeZone" => "Asia/Phnom_Penh", "MicrosoftTimeZoneIndex" => "205"),
                Array("MageTimeZone" => "Asia/Pontianak", "MicrosoftTimeZoneIndex" => "205"),
                Array("MageTimeZone" => "Asia/Saigon", "MicrosoftTimeZoneIndex" => "205"),
                Array("MageTimeZone" => "Asia/Vientiane", "MicrosoftTimeZoneIndex" => "205"),
                Array("MageTimeZone" => "Etc/GMT-7", "MicrosoftTimeZoneIndex" => "205"),
                Array("MageTimeZone" => "Indian/Christmas", "MicrosoftTimeZoneIndex" => "205"),
                Array("MageTimeZone" => "Pacific/Apia", "MicrosoftTimeZoneIndex" => "001"),
                Array("MageTimeZone" => "Asia/Brunei", "MicrosoftTimeZoneIndex" => "215"),
                Array("MageTimeZone" => "Asia/Kuala_Lumpur", "MicrosoftTimeZoneIndex" => "215"),
                Array("MageTimeZone" => "Asia/Kuching", "MicrosoftTimeZoneIndex" => "215"),
                Array("MageTimeZone" => "Asia/Makassar", "MicrosoftTimeZoneIndex" => "215"),
                Array("MageTimeZone" => "Asia/Manila", "MicrosoftTimeZoneIndex" => "215"),
                Array("MageTimeZone" => "Asia/Singapore", "MicrosoftTimeZoneIndex" => "215"),
                Array("MageTimeZone" => "Etc/GMT-8", "MicrosoftTimeZoneIndex" => "215"),
                Array("MageTimeZone" => "Africa/Blantyre", "MicrosoftTimeZoneIndex" => "140"),
                Array("MageTimeZone" => "Africa/Bujumbura", "MicrosoftTimeZoneIndex" => "140"),
                Array("MageTimeZone" => "Africa/Gaborone", "MicrosoftTimeZoneIndex" => "140"),
                Array("MageTimeZone" => "Africa/Harare", "MicrosoftTimeZoneIndex" => "140"),
                Array("MageTimeZone" => "Africa/Johannesburg", "MicrosoftTimeZoneIndex" => "140"),
                Array("MageTimeZone" => "Africa/Kigali", "MicrosoftTimeZoneIndex" => "140"),
                Array("MageTimeZone" => "Africa/Lubumbashi", "MicrosoftTimeZoneIndex" => "140"),
                Array("MageTimeZone" => "Africa/Lusaka", "MicrosoftTimeZoneIndex" => "140"),
                Array("MageTimeZone" => "Africa/Maputo", "MicrosoftTimeZoneIndex" => "140"),
                Array("MageTimeZone" => "Africa/Maseru", "MicrosoftTimeZoneIndex" => "140"),
                Array("MageTimeZone" => "Africa/Mbabane", "MicrosoftTimeZoneIndex" => "140"),
                Array("MageTimeZone" => "Etc/GMT-2", "MicrosoftTimeZoneIndex" => "140"),
                Array("MageTimeZone" => "Asia/Colombo", "MicrosoftTimeZoneIndex" => "200"),
                Array("MageTimeZone" => "Asia/Damascus", "MicrosoftTimeZoneIndex" => "158"),
                Array("MageTimeZone" => "Asia/Taipei", "MicrosoftTimeZoneIndex" => "220"),
                Array("MageTimeZone" => "Australia/Currie", "MicrosoftTimeZoneIndex" => "265"),
                Array("MageTimeZone" => "Australia/Hobart", "MicrosoftTimeZoneIndex" => "265"),
                Array("MageTimeZone" => "Asia/Dili", "MicrosoftTimeZoneIndex" => "235"),
                Array("MageTimeZone" => "Asia/Jayapura", "MicrosoftTimeZoneIndex" => "235"),
                Array("MageTimeZone" => "Asia/Tokyo", "MicrosoftTimeZoneIndex" => "235"),
                Array("MageTimeZone" => "Etc/GMT-9", "MicrosoftTimeZoneIndex" => "235"),
                Array("MageTimeZone" => "Pacific/Palau", "MicrosoftTimeZoneIndex" => "235"),
                Array("MageTimeZone" => "Etc/GMT-13", "MicrosoftTimeZoneIndex" => "300"),
                Array("MageTimeZone" => "Pacific/Enderbury", "MicrosoftTimeZoneIndex" => "300"),
                Array("MageTimeZone" => "Pacific/Fakaofo", "MicrosoftTimeZoneIndex" => "300"),
                Array("MageTimeZone" => "Pacific/Tongatapu", "MicrosoftTimeZoneIndex" => "300"),
                Array("MageTimeZone" => "Europe/Istanbul", "MicrosoftTimeZoneIndex" => "130"),
                Array("MageTimeZone" => "America/Indiana/Marengo", "MicrosoftTimeZoneIndex" => "040"),
                Array("MageTimeZone" => "America/Indiana/Vevay", "MicrosoftTimeZoneIndex" => "040"),
                Array("MageTimeZone" => "America/Indianapolis", "MicrosoftTimeZoneIndex" => "040"),
                Array("MageTimeZone" => "America/Creston", "MicrosoftTimeZoneIndex" => "015"),
                Array("MageTimeZone" => "America/Dawson_Creek", "MicrosoftTimeZoneIndex" => "015"),
                Array("MageTimeZone" => "America/Hermosillo", "MicrosoftTimeZoneIndex" => "015"),
                Array("MageTimeZone" => "America/Phoenix", "MicrosoftTimeZoneIndex" => "015"),
                Array("MageTimeZone" => "Etc/GMT+7", "MicrosoftTimeZoneIndex" => "015"),
                Array("MageTimeZone" => "America/Danmarkshavn", "MicrosoftTimeZoneIndex" => "085"),
                Array("MageTimeZone" => "Etc/GMT", "MicrosoftTimeZoneIndex" => "085"),
                Array("MageTimeZone" => "Etc/GMT-12", "MicrosoftTimeZoneIndex" => "285"),
                Array("MageTimeZone" => "Pacific/Funafuti", "MicrosoftTimeZoneIndex" => "285"),
                Array("MageTimeZone" => "Pacific/Kwajalein", "MicrosoftTimeZoneIndex" => "285"),
                Array("MageTimeZone" => "Pacific/Majuro", "MicrosoftTimeZoneIndex" => "285"),
                Array("MageTimeZone" => "Pacific/Nauru", "MicrosoftTimeZoneIndex" => "285"),
                Array("MageTimeZone" => "Pacific/Tarawa", "MicrosoftTimeZoneIndex" => "285"),
                Array("MageTimeZone" => "Pacific/Wake", "MicrosoftTimeZoneIndex" => "285"),
                Array("MageTimeZone" => "Pacific/Wallis", "MicrosoftTimeZoneIndex" => "285"),
                Array("MageTimeZone" => "America/Noronha", "MicrosoftTimeZoneIndex" => "075"),
                Array("MageTimeZone" => "Atlantic/South_Georgia", "MicrosoftTimeZoneIndex" => "075"),
                Array("MageTimeZone" => "Etc/GMT+2", "MicrosoftTimeZoneIndex" => "075"),
                Array("MageTimeZone" => "Etc/GMT+11", "MicrosoftTimeZoneIndex" => "280"),
                Array("MageTimeZone" => "Pacific/Midway", "MicrosoftTimeZoneIndex" => "280"),
                Array("MageTimeZone" => "Pacific/Niue", "MicrosoftTimeZoneIndex" => "280"),
                Array("MageTimeZone" => "Pacific/Pago_Pago", "MicrosoftTimeZoneIndex" => "280"),
                Array("MageTimeZone" => "Asia/Choibalsan", "MicrosoftTimeZoneIndex" => "227"),
                Array("MageTimeZone" => "Asia/Ulaanbaatar", "MicrosoftTimeZoneIndex" => "227"),
                Array("MageTimeZone" => "America/Caracas", "MicrosoftTimeZoneIndex" => "055"),
                Array("MageTimeZone" => "Asia/Sakhalin", "MicrosoftTimeZoneIndex" => "270"),
                Array("MageTimeZone" => "Asia/Ust-Nera", "MicrosoftTimeZoneIndex" => "270"),
                Array("MageTimeZone" => "Asia/Vladivostok", "MicrosoftTimeZoneIndex" => "270"),
                Array("MageTimeZone" => "Antarctica/Casey", "MicrosoftTimeZoneIndex" => "225"),
                Array("MageTimeZone" => "Australia/Perth", "MicrosoftTimeZoneIndex" => "225"),
                Array("MageTimeZone" => "Africa/Algiers", "MicrosoftTimeZoneIndex" => "113"),
                Array("MageTimeZone" => "Africa/Bangui", "MicrosoftTimeZoneIndex" => "113"),
                Array("MageTimeZone" => "Africa/Brazzaville", "MicrosoftTimeZoneIndex" => "113"),
                Array("MageTimeZone" => "Africa/Douala", "MicrosoftTimeZoneIndex" => "113"),
                Array("MageTimeZone" => "Africa/Kinshasa", "MicrosoftTimeZoneIndex" => "113"),
                Array("MageTimeZone" => "Africa/Lagos", "MicrosoftTimeZoneIndex" => "113"),
                Array("MageTimeZone" => "Africa/Libreville", "MicrosoftTimeZoneIndex" => "113"),
                Array("MageTimeZone" => "Africa/Luanda", "MicrosoftTimeZoneIndex" => "113"),
                Array("MageTimeZone" => "Africa/Malabo", "MicrosoftTimeZoneIndex" => "113"),
                Array("MageTimeZone" => "Africa/Ndjamena", "MicrosoftTimeZoneIndex" => "113"),
                Array("MageTimeZone" => "Africa/Niamey", "MicrosoftTimeZoneIndex" => "113"),
                Array("MageTimeZone" => "Africa/Porto-Novo", "MicrosoftTimeZoneIndex" => "113"),
                Array("MageTimeZone" => "Africa/Tunis", "MicrosoftTimeZoneIndex" => "113"),
                Array("MageTimeZone" => "Etc/GMT-1", "MicrosoftTimeZoneIndex" => "113"),
                Array("MageTimeZone" => "Arctic/Longyearbyen", "MicrosoftTimeZoneIndex" => "110"),
                Array("MageTimeZone" => "Europe/Amsterdam", "MicrosoftTimeZoneIndex" => "110"),
                Array("MageTimeZone" => "Europe/Andorra", "MicrosoftTimeZoneIndex" => "110"),
                Array("MageTimeZone" => "Europe/Berlin", "MicrosoftTimeZoneIndex" => "110"),
                Array("MageTimeZone" => "Europe/Busingen", "MicrosoftTimeZoneIndex" => "110"),
                Array("MageTimeZone" => "Europe/Gibraltar", "MicrosoftTimeZoneIndex" => "110"),
                Array("MageTimeZone" => "Europe/Luxembourg", "MicrosoftTimeZoneIndex" => "110"),
                Array("MageTimeZone" => "Europe/Malta", "MicrosoftTimeZoneIndex" => "110"),
                Array("MageTimeZone" => "Europe/Monaco", "MicrosoftTimeZoneIndex" => "110"),
                Array("MageTimeZone" => "Europe/Oslo", "MicrosoftTimeZoneIndex" => "110"),
                Array("MageTimeZone" => "Europe/Rome", "MicrosoftTimeZoneIndex" => "110"),
                Array("MageTimeZone" => "Europe/San_Marino", "MicrosoftTimeZoneIndex" => "110"),
                Array("MageTimeZone" => "Europe/Stockholm", "MicrosoftTimeZoneIndex" => "110"),
                Array("MageTimeZone" => "Europe/Vaduz", "MicrosoftTimeZoneIndex" => "110"),
                Array("MageTimeZone" => "Europe/Vatican", "MicrosoftTimeZoneIndex" => "110"),
                Array("MageTimeZone" => "Europe/Vienna", "MicrosoftTimeZoneIndex" => "110"),
                Array("MageTimeZone" => "Europe/Zurich", "MicrosoftTimeZoneIndex" => "110"),
                Array("MageTimeZone" => "Antarctica/Mawson", "MicrosoftTimeZoneIndex" => "185"),
                Array("MageTimeZone" => "Asia/Aqtau", "MicrosoftTimeZoneIndex" => "185"),
                Array("MageTimeZone" => "Asia/Aqtobe", "MicrosoftTimeZoneIndex" => "185"),
                Array("MageTimeZone" => "Asia/Ashgabat", "MicrosoftTimeZoneIndex" => "185"),
                Array("MageTimeZone" => "Asia/Dushanbe", "MicrosoftTimeZoneIndex" => "185"),
                Array("MageTimeZone" => "Asia/Oral", "MicrosoftTimeZoneIndex" => "185"),
                Array("MageTimeZone" => "Asia/Samarkand", "MicrosoftTimeZoneIndex" => "185"),
                Array("MageTimeZone" => "Asia/Tashkent", "MicrosoftTimeZoneIndex" => "185"),
                Array("MageTimeZone" => "Etc/GMT-5", "MicrosoftTimeZoneIndex" => "TEST"),
                Array("MageTimeZone" => "Indian/Kerguelen", "MicrosoftTimeZoneIndex" => "185"),
                Array("MageTimeZone" => "Indian/Maldives", "MicrosoftTimeZoneIndex" => "185"),
                Array("MageTimeZone" => "Antarctica/DumontDUrville", "MicrosoftTimeZoneIndex" => "275"),
                Array("MageTimeZone" => "Etc/GMT-10", "MicrosoftTimeZoneIndex" => "275"),
                Array("MageTimeZone" => "Pacific/Guam", "MicrosoftTimeZoneIndex" => "275"),
                Array("MageTimeZone" => "Pacific/Port_Moresby", "MicrosoftTimeZoneIndex" => "275"),
                Array("MageTimeZone" => "Pacific/Saipan", "MicrosoftTimeZoneIndex" => "275"),
                Array("MageTimeZone" => "Pacific/Truk", "MicrosoftTimeZoneIndex" => "275"),
                Array("MageTimeZone" => "Asia/Khandyga", "MicrosoftTimeZoneIndex" => "240"),
                Array("MageTimeZone" => "Asia/Yakutsk", "MicrosoftTimeZoneIndex" => "240"),
            );
            foreach ($timeZones as $time) {
                if ($time['MageTimeZone'] == $timeZone) {
                    $result = $time['MicrosoftTimeZoneIndex'];
                }
            }
        }

        return $result;
    }

    /**
     * Get culture id needed for trial account.
     *
     * @return mixed
     */
    public function getCultureId()
    {
        $fallback = 'en_US';
        $supportedCultures = array(
            'en_US' => '1033',
            'en_GB' => '2057',
            'fr_FR' => '1036',
            'es_ES' => '3082',
            'de_DE' => '1031',
            'it_IT' => '1040',
            'ru_RU' => '1049',
            'pt_PT' => '2070'
        );
        $localeCode = Mage::app()->getLocale()->getLocaleCode();
        if (isset($supportedCultures[$localeCode])) {
            return $supportedCultures[$localeCode];
        }

        return $supportedCultures[$fallback];
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
        $apiModel = Mage::helper('ddg')->getWebsiteApiClient();

        if (! $apiModel) {
            return false;
        } else {
            $dataFields = Mage::getModel('ddg_automation/connector_datafield')->getContactDatafields();
            foreach ($dataFields as $key => $dataField) {
                $response = $apiModel->postDataFields($dataField);
                //ignore existing datafields message
                if (isset($response->message) &&
                    $response->message != Dotdigitalgroup_Email_Model_Apiconnector_Client::API_ERROR_DATAFIELD_EXISTS
                ) {
                    $error = true;
                } else {
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

            if ($error) {
                return false;
            } else {
                Mage::getConfig()->cleanCache();
                return true;
            }
        }
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
        $addressBookMap = array(
            'Magento_Customers' => Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOMERS_ADDRESS_BOOK_ID,
            'Magento_Subscribers'=> Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SUBSCRIBERS_ADDRESS_BOOK_ID,
            'Magento_Guests' => Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_GUEST_ADDRESS_BOOK_ID
        );
        $error = false;
        $client = Mage::helper('ddg')->getWebsiteApiClient();

        if (! $client) {
            return false;
        } else {
            foreach ($addressBooks as $addressBook) {
                $addressBookName = $addressBook['name'];
                $visibility = $addressBook['visibility'];
                if ($addressBookName !== '') {
                    $response = $client->postAddressBooks($addressBookName, $visibility);
                    if (isset($response->message)) {
                        $error = true;
                    } else {
                        try {
                            //@codingStandardsIgnoreStart
                            //map the successfully created address book
                            $config = new Mage_Core_Model_Config();
                            //@codingStandardsIgnoreEnd
                            $config->saveConfig($addressBookMap[$addressBookName], $response->id);
                            Mage::helper('ddg')->log('successfully connected address book : ' . $addressBookName);
                        } catch (Exception $e) {
                            Mage::logException($e);
                            $error = true;
                        }
                    }
                }
            }
        }

        if ($error) {
            return false;
        } else {
            Mage::getConfig()->cleanCache();
            return true;
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
     * Save api endpoint.
     *
     * @param $value
     */
    public function saveApiEndPoint($value)
    {
        $config = Mage::getConfig();
        $config->saveConfig(
            Dotdigitalgroup_Email_Helper_Config::PATH_FOR_API_ENDPOINT,
            $value
        );
        $config->cleanCache();
    }

    /**
     * Check if both frotnend and backend secure(HTTPS).
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
}