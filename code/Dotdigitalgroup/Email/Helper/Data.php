<?php

class Dotdigitalgroup_Email_Helper_Data extends Mage_Core_Helper_Abstract
{

    public function isEnabled($website = 0)
    {
        $website = Mage::app()->getWebsite($website);
        return (bool)$website->getConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_API_ENABLED);
    }

    /**
     * @param int/object $website
     * @return mixed
     */
    public function getApiUsername($website = 0)
    {
        $website = Mage::app()->getWebsite($website);

        return $website->getConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_API_USERNAME);
    }

    public function getApiPassword($website = 0)
    {
        $website = Mage::app()->getWebsite($website);

        return $website->getConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_API_PASSWORD);
    }

    public function auth($authRequest)
    {
        if ($authRequest != Mage::getStoreConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_DYNAMIC_CONTENT_PASSCODE)) {
            $this->getRaygunClient()->Send('Authentication failed with code :' . $authRequest);
            throw new Exception('Authentication failed : ' . $authRequest);
        }
        return true;
    }

    public function getMappedCustomerId()
    {
        return Mage::getStoreConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_MAPPING_CUSTOMER_ID);
    }

    public function getMappedOrderId()
    {
        return Mage::getStoreConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_MAPPING_LAST_ORDER_ID);
    }

    public function getPasscode()
    {
        return Mage::getStoreConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_DYNAMIC_CONTENT_PASSCODE);
    }

    public function getLastOrderId()
    {
        return Mage::getStoreConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_MAPPING_LAST_ORDER_ID);

    }

    public function getLastQuoteId()
    {
        return Mage::getStoreConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_MAPPING_LAST_QUOTE_ID);

    }

    public function log($data, $level = Zend_Log::DEBUG, $filename = 'api.log')
    {
        if ($this->getDebugEnabled()) {
            $filename = 'connector_' . $filename;

            Mage::log($data, $level, $filename, $force = true);
        }
    }

    public function getDebugEnabled()
    {
        return (bool) Mage::getStoreConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_ADVANCED_DEBUG_ENABLED);
    }

    public function getConnectorVersion()
    {
        $modules = (array) Mage::getConfig()->getNode('modules')->children();
        if (isset($modules['Dotdigitalgroup_Email'])) {
            $moduleName = $modules['Dotdigitalgroup_Email'];
            return $moduleName->version;
        }
        return '';
    }


    public function getPageTrackingEnabled()
    {
        return (bool)Mage::getStoreConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_PAGE_TRACKING_ENABLED);
    }

    public function getRoiTrackingEnabled()
    {
        return (bool)Mage::getStoreConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_ROI_TRACKING_ENABLED);
    }

    public function getResourceAllocationEnabled()
    {
        return (bool)Mage::getStoreConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_RESOURCE_ALLOCATION);
    }

    public function getMappedStoreName($website)
    {
        $mapped = $website->getConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_MAPPING_CUSTOMER_STORENAME);
        $storeName = ($mapped)? $mapped : '';
        return  $storeName;
    }

    /**
     * Get the contact id for the custoemer based on website id.
     * @param $email
     * @param $websiteId
     *
     * @return bool
     */
    public function getContactId($email, $websiteId)
    {
        $client = $this->getWebsiteApiClient($websiteId);
        $response = $client->postContacts($email);

        if (isset($response->message))
            return $response->message;

        return $response->id;
    }

    public function getCustomerAddressBook($website)
    {
        $website = Mage::app()->getWebsite($website);
        return $website->getConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOMERS_ADDRESS_BOOK_ID);
    }

    public function getSubscriberAddressBook($website)
    {
        $website = Mage::app()->getWebsite($website);
        return $website->getConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SUBSCRIBERS_ADDRESS_BOOK_ID);
    }

    public function getGuestAddressBook($website)
    {
        $website = Mage::app()->getWebsite($website);
        return $website->getConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_GUEST_ADDRESS_BOOK_ID);
    }

    /**
     * @return $this
     */
    public  function allowResourceFullExecution()
    {
        if ($this->getResourceAllocationEnabled()) {

            /* it may be needed to set maximum execution time of the script to longer,
             * like 60 minutes than usual */
            set_time_limit(7200);

            /* and memory to 512 megabytes */
            ini_set('memory_limit', '512M');
        }
        return $this;
    }
    public function convert($size)
    {
        $unit=array('b','kb','mb','gb','tb','pb');
        return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
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
            $accounts[$apiUsername] = $apiUsername . ', websiteId: ' . $websiteId . ' name ' . $website->getName();
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
        $attr = $website->getConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_MAPPING_CUSTOM_DATAFIELDS);

        if (!$attr)
            return array();

        return unserialize($attr);
    }

    /**
     * @param $path
     * @param null|string|bool|int|Mage_Core_Model_Website $websiteId
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
     * @param int $website
     *
     * @return bool|Dotdigitalgroup_Email_Model_Apiconnector_Client
     */
    public function getWebsiteApiClient($website = 0)
    {
        if (! $apiUsername = $this->getApiUsername($website) || ! $apiPassword = $this->getApiPassword($website))
            return false;

        $client = Mage::getModel('email_connector/apiconnector_client');
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
     * Autorisation url.
     * @return string
     */
    public function getAuthoriseUrl()
    {
        $clientId = Mage::getStoreConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CLIENT_ID);
        $baseUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB, true);
        $callback    = $baseUrl . 'connector/email/callback';
        $adminUser = Mage::getSingleton('admin/session')->getUser();

	    //query params
        $params = array(
            'redirect_uri' => $callback,
            'scope' => 'Account',
            'state' => $adminUser->getId(),
            'response_type' => 'code'
        );
        $url = Dotdigitalgroup_Email_Helper_Config::API_CONNECTOR_URL_AUTHORISE . http_build_query($params) . '&client_id=' . $clientId;

        return $url;
    }

    /**
     * order status config value
     * @param int $website
     * @return mixed order status
     */
    public function getConfigSelectedStatus($website = 0)
    {
        $status = $this->getWebsiteConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_ORDER_STATUS, $website);
        if($status)
            return explode(',',$status);
        else
            return false;
    }

    public function getConfigSelectedCustomOrderAttributes($website = 0)
    {
        $customAttributes = $this->getWebsiteConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOM_ORDER_ATTRIBUTES, $website);
        if($customAttributes)
            return explode(',',$customAttributes);
        else
            return false;
    }

    public function getConfigSelectedCustomQuoteAttributes($website = 0)
    {
        $customAttributes = $this->getWebsiteConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOM_QUOTE_ATTRIBUTES, $website);
        if($customAttributes)
            return explode(',',$customAttributes);
        else
            return false;
    }

    /**
     * check sweet tooth installed/active status
     * @return boolean
     */
    public function isSweetToothEnabled()
    {
        return (bool)Mage::getConfig()->getModuleConfig('TBT_Rewards')->is('active', 'true');
    }

    /**
     * check sweet tooth installed/active status and active status
     * @param Mage_Core_Model_Website $website
     * @return boolean
     */
    public function isSweetToothToGo($website)
    {
        $stMappingStatus = $this->getWebsiteConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_MAPPING_SWEETTOOTH_ACTIVE, $website);
        if($stMappingStatus && $this->isSweetToothEnabled()) return true;
        return false;
    }

    public function setConnectorContactToReImport($customerId)
    {
        $contactModel = Mage::getModel('email_connector/contact');
        $contactModel
            ->loadByCustomerId($customerId)
            ->setEmailImported(Dotdigitalgroup_Email_Model_Contact::EMAIL_CONTACT_NOT_IMPORTED)
            ->save();
    }

    /**
     * Diff between to times;
     *
     * @param $time1
     * @param $time2
     * @return int
     */
    public function dateDiff($time1, $time2=NULL) {
        if (is_null($time2)) {
            $time2 = Mage::getModel('core/date')->date();
        }
        $time1 = strtotime($time1);
        $time2 = strtotime($time2);
        return $time2 - $time1;
    }


    /**
     * Disable website config when the request is made admin area only!
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
     * number of customers with duplicate emails, emails as total number
     * @return Mage_Customer_Model_Resource_Customer_Collection
     */
    public function getCustomersWithDuplicateEmails( ) {
        $customers = Mage::getModel('customer/customer')->getCollection();

        //duplicate emails
        $customers->getSelect()
            ->columns(array('emails' => 'COUNT(e.entity_id)'))
            ->group('email')
            ->having('emails > ?', 1);

        return $customers;
    }

    /**
     * Create new raygun client.
     *
     * @return bool|\Raygun4php\RaygunClient
     */
    public function getRaygunClient()
    {
        $code = Mage::getstoreConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_RAYGUN_APPLICATION_CODE);

        if ($this->raygunEnabled()) {
            require_once Mage::getBaseDir('lib') . DS . 'Raygun4php' . DS  . 'RaygunClient.php';
            return new Raygun4php\RaygunClient($code, false, true);
        }

        return false;
    }

    /**
     * Raygun logs.
     * @param int $errno
     * @param $message
     * @param string $filename
     * @param int $line
     * @param array $tags
     *
     * @return int|null
     */
    public function rayLog($errno = 100, $message, $filename = 'helper/data.php', $line = 1, $tags = array())
    {
        $client = $this->getRaygunClient();
        if ($client) {
            //use tags to log the client baseurl
            if (empty($tags))
                $tags = array(Mage::getBaseUrl('web'));
            //send message
            $code = $client->SendError( $errno, $message, $filename, $line, $tags );

            return $code;
        }

        return false;
    }


    /**
     * check for raygun application and if enabled.
     * @param int $websiteId
     *
     * @return mixed
     * @throws Mage_Core_Exception
     */
    public function raygunEnabled($websiteId = 0)
    {
        $website = Mage::app()->getWebsite($websiteId);

        return  (bool)$website->getConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_RAYGUN_APPLICATION_CODE);

    }


    /**
     * Create new config if the config was not found.
     * mark the account api datafields was created
     * @param $value
     * @param string $scope
     *
     * @return bool
     */
    public function isConfigCreatedForPath( $value, $scope = 'default' )
    {
        $configModel = Mage::getModel('email_connector/config');

        //we use path as the transactional usename config value
        $path = Dotdigitalgroup_Email_Helper_Transactional::XML_PATH_TRANSACTIONAL_API_USERNAME;

        $itemConfig = $configModel->getCollection()
            ->addFieldToFilter('path', $path)
            ->addFieldToFilter('value', $value)
            ->addFieldToFilter('scope', $scope)
            ->getFirstItem();

        //config was created
        if ($itemConfig->getId()) {
            return true;
        }

        //new config save data
        $itemConfig->setPath($path)
            ->setScope($scope)
            ->setValue($value)
            ->save();
        return false;
    }

    /**
     * Generate the baseurl for the default store
     * dynamic content will be displayed
     * @return string
     * @throws Mage_Core_Exception
     */
	public function generateDynamicUrl()
	{
		$website = Mage::app()->getRequest()->getParam('website', false);

		//set website url for the default store id
		$website = ($website)? Mage::app()->getWebsite( $website ) : 0;

		$defaultGroup = Mage::app()->getWebsite($website)
		                    ->getDefaultGroup();

		if (! $defaultGroup)
			return $mage = Mage::app()->getStore()->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);

		//base url
		$baseUrl = Mage::app()->getStore($defaultGroup->getDefaultStore())->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);

		return $baseUrl;

	}

    /**
     *
     *
     * @param int $store
     * @return mixed
     */
    public function isNewsletterSuccessDisabled($store = 0)
    {
        return Mage::getStoreConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_DISABLE_NEWSLETTER_SUCCESS, $store);
    }

    /**
     * get sales_flat_order table description
     *
     * @return array
     */
    public function getOrderTableDescription()
    {
        $resource = Mage::getSingleton('core/resource');
        $readConnection = $resource->getConnection('core_read');
        $salesTable = $resource->getTableName('sales/order');

        return $readConnection->describeTable($salesTable);
    }

    /**
     * get sales_flat_quote table description
     *
     * @return array
     */
    public function getQuoteTableDescription()
    {
        $resource = Mage::getSingleton('core/resource');
        $readConnection = $resource->getConnection('core_read');
        $table = $resource->getTableName('sales/quote');
        return $readConnection->describeTable($table);
    }

    /**
     * @return bool
     */
    public function getEasyEmailCapture()
    {
        return Mage::getStoreConfigFlag(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_EMAIL_CAPTURE);
    }

    /**
     * get feefo logon config value
     *
     * @return mixed
     */
    public function getFeefoLogon()
    {
        return $this->getWebsiteConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_DYNAMIC_CONTENT_FEEFO_LOGON);
    }

    /**
     * get feefo reviews limit config value
     *
     * @return mixed
     */
    public function getFeefoReviewsPerProduct()
    {
        return $this->getWebsiteConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_DYNAMIC_CONTENT_FEEFO_REVIEWS);
    }

    /**
     * get feefo logo template config value
     *
     * @return mixed
     */
    public function getFeefoLogoTemplate()
    {
        return $this->getWebsiteConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_DYNAMIC_CONTENT_FEEFO_TEMPLATE);
    }
}
