<?php

class Dotdigitalgroup_Email_Model_Sales_Order
{
	/**
	 * @var array
	 */
	protected $accounts = array();
	/**
	 * @var string
	 */
	private $_apiUsername;
	/**
	 * @var string
	 */
	private $_apiPassword;

	/**
	 * Global number of orders
	 * @var int
	 */
	private $_countOrders = 0;

    private $_reviewCollection = array();

    /**
     * initial sync the transactional data
     * @return array
     */
    public function sync()
    {
        $response = array('success' => true, 'message' => '');
        $client = Mage::getModel('ddg_automation/apiconnector_client');
        // Initialise a return hash containing results of our sync attempt
        $this->_searchAccounts();
        foreach ($this->accounts as $account) {
            $orders = $account->getOrders();
            $numOrders = count($orders);
            $this->_countOrders += $numOrders;
            //send transactional for any number of orders set
            if ($numOrders) {
                $client->setApiUsername($account->getApiUsername())
                    ->setApiPassword($account->getApiPassword());
                Mage::helper('ddg')->log('--------- Order sync ---------- : ' . count($orders));
                $client->postContactsTransactionalDataImport($orders, 'Orders');
                Mage::helper('ddg')->log('----------end order sync----------');
            }
            unset($this->accounts[$account->getApiUsername()]);
        }

        if ($this->_countOrders)
            $response['message'] = 'Number of updated orders : ' . $this->_countOrders;
        return $response;
    }

    /**
     * Search the configuration data per website
     */
    private function _searchAccounts()
    {
        $helper = Mage::helper('ddg');
        foreach (Mage::app()->getWebsites(true) as $website) {
            if ($helper->getWebsiteConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_ORDER_ENABLED, $website)) {

                $this->_apiUsername = $helper->getApiUsername($website);
                $this->_apiPassword = $helper->getApiPassword($website);

                // limit for orders included to sync
                $limit = Mage::helper('ddg')->getWebsiteConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_TRANSACTIONAL_DATA_SYNC_LIMIT, $website);
                if (!isset($this->accounts[$this->_apiUsername])) {
                    $account = Mage::getModel('ddg_automation/connector_account')
                        ->setApiUsername($this->_apiUsername)
                        ->setApiPassword($this->_apiPassword);
                    $this->accounts[$this->_apiUsername] = $account;
                }
                $this->accounts[$this->_apiUsername]->setOrders($this->getConnectorOrders($website, $limit));
            }
        }
    }

    /**
     * get all order to import.
     * @param $website
     * @param int $limit
     * @return array
     */
    public function getConnectorOrders($website, $limit = 100)
    {
        $orders = $customers = array();
        $storeIds = $website->getStoreIds();
        $orderModel   = Mage::getModel('ddg_automation/order');
        if(empty($storeIds))
            return array();

        $helper = Mage::helper('ddg');
        $orderStatuses = $helper->getConfigSelectedStatus($website);

        if($orderStatuses)
            $orderCollection = $orderModel->getOrdersToImport($storeIds, $limit, $orderStatuses);
        else
            return array();

        foreach ($orderCollection as $order) {
            try {
                $salesOrder = Mage::getModel('sales/order')->load($order->getOrderId());
                $storeId = $order->getStoreId();
                $websiteId = Mage::app()->getStore($storeId)->getWebsiteId();
                /**
                 * Add guest to contacts table.
                 */
                if ($salesOrder->getCustomerIsGuest()) {
                    $this->_createGuestContact($salesOrder->getCustomerEmail(), $websiteId, $storeId);
                }
                if ($salesOrder->getId()) {
                    $connectorOrder = Mage::getModel('ddg_automation/connector_order', $salesOrder);
                    $orders[] = $connectorOrder;
                }
                $order->setEmailImported(Dotdigitalgroup_Email_Model_Contact::EMAIL_CONTACT_IMPORTED)
                    ->save();
            }catch(Exception $e){
                Mage::logException($e);
            }
        }
        return $orders;
    }

	/**
	 * Create a guest contact.
	 * @param $email
	 * @param $websiteId
	 * @param $storeId
	 *
	 * @return bool
	 */
	private function _createGuestContact($email, $websiteId, $storeId){
        try{
            $client = Mage::helper('ddg')->getWebsiteApiClient($websiteId);

	        //no api credentials or the guest has no been mapped
	        if (! $client || ! $addressBookId = Mage::helper('ddg')->getGuestAddressBook($websiteId))
		        return false;
	        //check if contact exists, create if not
	        $contactApi = $client->postContacts($email);

	        //cannot continue error creating contact
            if (isset($contactApi->message)) {
                return false;
            }

            //add guest to address book
	        $response = $client->postAddressBookContacts($addressBookId, $contactApi);

	        //set contact as was found as guest and
            $contactModel = Mage::getModel('ddg_automation/contact')->loadByCustomerEmail($email, $websiteId);
            $contactModel->setIsGuest(1)
                ->setStoreId($storeId)
                ->setEmailImported(1);
	        //contact id
	        if (isset($contactApi->id))
		        $contactModel->setContactId();

	        //mark the contact as surpressed
            if (isset($response->message) && $response->message == 'Contact is suppressed. ERROR_CONTACT_SUPPRESSED')
                $contactModel->setSuppressed(1);

	        //save
            $contactModel->save();

            Mage::helper('ddg')->log('-- guest found : '  . $email . ' website : ' . $websiteId . ' ,store : ' . $storeId);
        }catch(Exception $e){
	        Mage::helper('ddg')->getRaygunClient()->SendException($e, array(Mage::getBaseUrl('web')));
            Mage::logException($e);
        }

        return true;
    }

    /**
     * create review campaigns
     *
     * @return bool
     */
    public function createReviewCampaigns()
    {
        $this->searchOrdersForReview();

        foreach($this->_reviewCollection as $websiteId => $collection){
            $this->registerCampaign($collection, $websiteId);
        }
    }

    /**
     * register review campaign
     *
     * @param $collection
     * @param $websiteId
     *
     * @throws Exception
     */
    private function registerCampaign($collection, $websiteId)
    {
        $helper = Mage::helper('ddg/review');
        $campaignId = $helper->getCampaign($websiteId);

        if($campaignId) {
            foreach ($collection as $order) {
                Mage::helper('ddg')->log('-- Order Review: ' . $order->getIncrementId() . ' Campaign Id: ' . $campaignId);

                try {
                    $emailCampaign = Mage::getModel('ddg_automation/campaign');
                    $emailCampaign
                        ->setEmail($order->getCustomerEmail())
                        ->setStoreId($order->getStoreId())
                        ->setCampaignId($campaignId)
                        ->setEventName('Order Review')
                        ->setCreatedAt(Mage::getSingleton('core/date')->gmtDate())
                        ->setOrderIncrementId($order->getIncrementId())
                        ->setQuoteId($order->getQuoteId());

                    if($order->getCustomerId())
                        $emailCampaign->setCustomerId($order->getCustomerId());

                    $emailCampaign->save();
                } catch (Exception $e) {
                    Mage::logException($e);
                }
            }
        }
    }

    /**
     * search for orders to review per website
     */
    private function searchOrdersForReview()
    {
        $helper = Mage::helper('ddg/review');

        foreach (Mage::app()->getWebsites(true) as $website){
            if($helper->isEnabled($website) &&
                $helper->getOrderStatus($website) &&
                    $helper->getDelay($website)){

                $storeIds = $website->getStoreIds();
                if(empty($storeIds))
                    return;

                $orderStatusFromConfig = $helper->getOrderStatus($website);
                $delayInDays = $helper->getDelay($website);

                $campaignCollection = Mage::getModel('ddg_automation/campaign')->getCollection();
                $campaignCollection
                    ->addFieldToFilter('event_name', 'Order Review')
                    ->load();

                $campaignOrderIds = $campaignCollection->getColumnValues('order_increment_id');

                $to = Mage::app()->getLocale()->date()
                    ->subDay($delayInDays);
                $from = clone $to;
                $to = $to->toString('YYYY-MM-dd HH:mm:ss');
                $from = $from->subHour(2)
                    ->toString('YYYY-MM-dd HH:mm:ss');

                $created = array( 'from' => $from, 'to' => $to, 'date' => true);

                $collection = Mage::getModel('sales/order')->getCollection();
                    $collection->addFieldToFilter('status', $orderStatusFromConfig)
                    ->addFieldToFilter('created_at', $created)
                    ->addFieldToFilter('store_id', array('in' => $storeIds));

                if(!empty($campaignOrderIds))
                    $collection->addFieldToFilter('increment_id', array('nin' => $campaignOrderIds));

                $collection->load();

                if($collection->getSize())
                    $this->_reviewCollection[$website->getId()] = $collection;
            }
        }
    }

    /**
     * get customer last order id
     *
     * @param Mage_Customer_Model_Customer $customer
     * @return bool|Varien_Object
     */
    public function getCustomerLastOrderId(Mage_Customer_Model_Customer $customer)
    {
        $storeIds = Mage::app()->getWebsite($customer->getWebsiteId())->getStoreIds();
        $collection = Mage::getModel('sales/order')->getCollection();
        $collection->addFieldToFilter('customer_id', $customer->getId())
            ->addFieldToFilter('store_id', array('in' => $storeIds))
            ->setPageSize(1)
            ->setOrder('entity_id');

        if ($collection->count())
            return $collection->getFirstItem();
        else
            return false;
    }

    /**
     * get customer last quote id
     *
     * @param Mage_Customer_Model_Customer $customer
     * @return bool|Varien_Object
     */
    public function getCustomerLastQuoteId(Mage_Customer_Model_Customer $customer)
    {
        $storeIds = Mage::app()->getWebsite($customer->getWebsiteId())->getStoreIds();
        $collection = Mage::getModel('sales/quote')->getCollection();
        $collection->addFieldToFilter('customer_id', $customer->getId())
            ->addFieldToFilter('store_id', array('in' => $storeIds))
            ->setPageSize(1)
            ->setOrder('entity_id');

        if ($collection->count())
            return $collection->getFirstItem();
        else
            return false;
    }
}