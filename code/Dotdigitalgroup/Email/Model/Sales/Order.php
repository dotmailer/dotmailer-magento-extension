<?php

class Dotdigitalgroup_Email_Model_Sales_Order
{

    /**
     * @var array
     */
    public $accounts = array();
    /**
     * @var string
     */
    public $apiUsername;
    /**
     * @var string
     */
    public $apiPassword;
    /**
     * Global number of orders
     *
     * @var int
     */
    public $countOrders = 0;
    /**
     * @var
     */
    public $orderIds;
    /**
     * @var array
     */
    public $orderReminderReviewArray = array();
    /**
     * @var
     */
    public $orderIdsForSingleSync;
    /**
     * @var array
     */
    protected $_guests = array();

    /**
     * Initial sync the transactional data.
     *
     * @return array
     */
    public function sync()
    {
        $response = array('success' => true, 'message' => '');
        // Initialise a return hash containing results of our sync attempt
        $this->_searchAccounts();

        foreach ($this->accounts as $account) {
            $orders = $account->getOrders();
            $orderIds = $account->getOrderIds();
            $ordersForSingleSync = $account->getOrdersForSingleSync();
            $orderIdsForSingleSync = $account->getOrderIdsForSingleSync();
            $website = $account->getWebsites();
            //@codingStandardsIgnoreStart
            $numOrders = count($orders);
            $numOrdersForSingleSync = count($ordersForSingleSync);
            //@codingStandardsIgnoreEnd
            $this->countOrders += $numOrders;
            $this->countOrders += $numOrdersForSingleSync;
            //send transactional for any number of orders set
            if ($numOrders) {
                Mage::helper('ddg')->log(
                    '--------- register Order sync with importer ---------- : '
                    . $numOrders
                );
                //register in queue with importer
                $check = Mage::getModel('ddg_automation/importer')
                    ->registerQueue(
                        Dotdigitalgroup_Email_Model_Importer::IMPORT_TYPE_ORDERS,
                        $orders,
                        Dotdigitalgroup_Email_Model_Importer::MODE_BULK,
                        $website[0]
                    );
                //if no error then set imported
                if ($check) {
                    $this->_setImported($orderIds);
                }

                Mage::helper('ddg')->log('----------end order sync----------');
            }

            if ($numOrdersForSingleSync) {
                $error = false;
                foreach ($ordersForSingleSync as $order) {
                    //register in queue with importer
                    $check = Mage::getModel('ddg_automation/importer')
                        ->registerQueue(
                            Dotdigitalgroup_Email_Model_Importer::IMPORT_TYPE_ORDERS,
                            $order,
                            Dotdigitalgroup_Email_Model_Importer::MODE_SINGLE,
                            $website[0]
                        );
                    if (!$check) {
                        $error = true;
                    }
                }

                //if no error then set imported
                if (!$error) {
                    $this->_setImported($orderIdsForSingleSync, true);
                }
            }

            unset($this->accounts[$account->getApiUsername()]);
        }

        /**
         * Add guest to contacts table.
         */
        if (! empty($this->_guests)) {
            Mage::getResourceModel('ddg_automation/contact')->insertGuest($this->_guests);
        }

        if ($this->countOrders) {
            $response['message'] = 'Number of updated orders : '
                . $this->countOrders;
        }

        return $response;
    }

    /**
     * Search the configuration data per website.
     */
    protected function _searchAccounts()
    {

        $helper = Mage::helper('ddg');

        foreach (Mage::app()->getWebsites(true) as $website) {
            $this->orderIds = array();
            $this->orderIdsForSingleSync = array();
            $apiEnabled = $helper->getWebsiteConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_API_ENABLED,
                $website
            );
            $storeIds = $website->getStoreIds();
            if ($apiEnabled
                && $helper->getWebsiteConfig(
                    Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_ORDER_ENABLED,
                    $website
                )
                &&
                !empty($storeIds)
            ) {
                $this->apiUsername = $helper->getApiUsername($website);
                $this->apiPassword = $helper->getApiPassword($website);

                // limit for orders included to sync
                $limit = Mage::helper('ddg')->getWebsiteConfig(
                    Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_TRANSACTIONAL_DATA_SYNC_LIMIT,
                    $website
                );
                if (!isset($this->accounts[$this->apiUsername])) {
                    $account = Mage::getModel(
                        'ddg_automation/connector_account'
                    )
                        ->setApiUsername($this->apiUsername)
                        ->setApiPassword($this->apiPassword);
                    $this->accounts[$this->apiUsername] = $account;
                }

                $this->accounts[$this->apiUsername]->setOrders(
                    $this->getConnectorOrders($website, $limit)
                );
                $orderIds = array_merge(
                    $this->accounts[$this->apiUsername]->getOrderIds(),
                    $this->orderIds
                );
                $this->accounts[$this->apiUsername]->setOrderIds($orderIds);
                $this->accounts[$this->apiUsername]->setWebsites(
                    $website->getId()
                );
                $this->accounts[$this->apiUsername]->setOrdersForSingleSync(
                    $this->getConnectorOrders($website, $limit, true)
                );
                $orderIdsForSingleSync = array_merge(
                    $this->accounts[$this->apiUsername]->getOrderIdsForSingleSync(),
                    $this->orderIdsForSingleSync
                );
                $this->accounts[$this->apiUsername]->setOrderIdsForSingleSync(
                    $orderIdsForSingleSync
                );
            }
        }
    }

    /**
     * Get all order to import.
     *
     * @param     $website
     * @param int $limit
     * @param     $modified
     *
     * @return array
     */
    public function getConnectorOrders($website, $limit = 100, $modified = false)
    {
        $orders = $customers = array();
        $storeIds = $website->getStoreIds();
        $orderModel = Mage::getModel('ddg_automation/order');

        if (empty($storeIds)) {
            return array();
        }

        $helper = Mage::helper('ddg');
        $orderStatuses = $helper->getConfigSelectedStatus($website);

        if ($orderStatuses) {
            if ($modified) {
                $orderCollection = $orderModel->getOrdersToImport(
                    $storeIds, $limit, $orderStatuses, true
                );
            } else {
                $orderCollection = $orderModel->getOrdersToImport(
                    $storeIds, $limit, $orderStatuses
                );
            }
        } else {
            return array();
        }

        //email_order order ids
        $orderIds = $orderCollection->getColumnValues('order_id');
        //get the order collection
        $salesOrderCollection = Mage::getResourceModel('sales/order_collection')
            ->addFieldToFilter('entity_id', array('in' => $orderIds));
        try {
            foreach ($salesOrderCollection as $order) {
                $storeId = $order->getStoreId();
                $websiteId = Mage::app()->getStore($storeId)->getWebsiteId();
                /**
                 * Add guest to array to add to contacts table.
                 */
                if ($order->getCustomerIsGuest()
                    && $order->getCustomerEmail()
                ) {
                    //add guest to the list
                    if (! isset($this->_guests[$order->getCustomerEmail()])) {
                        $this->_guests[$order->getCustomerEmail()] = array(
                            'email' => $order->getCustomerEmail(),
                            'website_id' => $websiteId,
                            'store_id' => $storeId,
                            'is_guest' => 1
                        );
                    }
                }

                if ($order->getId()) {
                    $connectorOrder = Mage::getModel(
                        'ddg_automation/connector_order'
                    );
                    $connectorOrder->setOrderData($order);
                    $orders[] = $connectorOrder;
                }

                if ($modified) {
                    $this->orderIdsForSingleSync[] = $order->getId();
                } else {
                    $this->orderIds[] = $order->getId();
                }
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }

        return $orders;
    }

    /**
     * Create product reminder campaigns.
     */
    public function createProductReminderReviewCampaigns()
    {
        $this->searchOrdersForProductReminder();

        foreach ($this->orderReminderReviewArray as $websiteId => $collection) {
            $this->registerCampaign($collection, $websiteId);
        }
    }

    /**
     * Register review campaign.
     *
     * @param $collection
     * @param $websiteId
     *
     * @throws Exception
     */
    protected function registerCampaign($collection, $websiteId)
    {
        $helper = Mage::helper('ddg');
        $campaignId = $helper->getReviewReminderCampaign($websiteId);
        //campaign id is selected in config
        if ($campaignId) {
            foreach ($collection as $order) {
                $helper->log(
                    '-- Register campaign : ' . $campaignId . ' for product reminder, order_increment_id : ' .
                    $order->getIncrementId()
                );

                try {
                    $emailCampaign = Mage::getModel('ddg_automation/campaign')
                        ->setEmail($order->getCustomerEmail())
                        ->setStoreId($order->getStoreId())
                        ->setCampaignId($campaignId)
                        ->setEventName('Order Review')
                        ->setCreatedAt(
                            Mage::getSingleton('core/date')->gmtDate()
                        )
                        ->setOrderIncrementId($order->getIncrementId())
                        ->setQuoteId($order->getQuoteId());
                    //set customer id for campaign
                    if ($order->getCustomerId()) {
                        $emailCampaign->setCustomerId($order->getCustomerId());
                    }

                    //@codingStandardsIgnoreStart
                    $emailCampaign->save();
                    //@codingStandardsIgnoreEnd
                } catch (Exception $e) {
                    Mage::logException($e);
                }
            }
        }
    }

    /**
     * Search for orders for product review reminder.
     */
    protected function searchOrdersForProductReminder()
    {
        $helper = Mage::helper('ddg');

        foreach (Mage::app()->getWebsites(true) as $website) {
            $apiEnabled = $helper->getWebsiteConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_API_ENABLED,
                $website
            );
            $reviewReminderEnabled = $helper->isReviewReminderEnabled(
                $website
            );
            $reviewReminderOrderStatus = $helper->getReviewReminderOrderStatus(
                $website
            );
            $reviewReminderDelayInDays = $helper->getReviewReminderDelay(
                $website
            );
            //check for api and review enabled
            if ($apiEnabled && $reviewReminderEnabled
                && $reviewReminderOrderStatus
                && $reviewReminderDelayInDays
            ) {
                //check for website with no stores
                $storeIds = $website->getStoreIds();
                if (empty($storeIds)) {
                    continue;
                }

                $campaignCollection = Mage::getModel('ddg_automation/campaign')
                    ->getCollection()
                    ->addFieldToFilter('event_name', 'Order Review');

                $campaignOrderIds = $campaignCollection->getColumnValues(
                    'order_increment_id'
                );

                //@codingStandardsIgnoreStart
                //date time with config delay
                $date = Mage::app()->getLocale()->date()
                    ->subDay($reviewReminderDelayInDays);
                //@codingStandardsIgnoreEnd
                $from = clone $date;
                $to = $date->toString('YYYY-MM-dd HH:mm:ss');
                $from = $from->subHour(2)
                    ->toString('YYYY-MM-dd HH:mm:ss');
                //created at date range
                $createdAt = array('from' => $from, 'to' => $to,
                    'date' => true);

                $orderCollection = Mage::getModel('sales/order')->getCollection()
                    ->addFieldToFilter(
                        'main_table.status', $reviewReminderOrderStatus
                    )
                    ->addFieldToFilter('main_table.created_at', $createdAt)
                    ->addFieldToFilter(
                        'main_table.store_id', array('in' => $storeIds)
                    );

                if (!empty($campaignOrderIds)) {
                    $orderCollection->addFieldToFilter(
                        'main_table.increment_id',
                        array('nin' => $campaignOrderIds)
                    );
                }

                //process rules on collection
                $ruleModel = Mage::getModel('ddg_automation/rules');
                $collection = $ruleModel->process(
                    $orderCollection, Dotdigitalgroup_Email_Model_Rules::REVIEW,
                    $website->getId()
                );

                if ($collection->getSize()) {
                    $this->orderReminderReviewArray[$website->getId()]
                        = $collection;
                }
            }
        }
    }

    /**
     * Get customer last order id.
     *
     * @param Mage_Customer_Model_Customer $customer
     *
     * @return bool|Varien_Object
     */
    public function getCustomerLastOrderId(Mage_Customer_Model_Customer $customer)
    {
        $storeIds = Mage::app()->getWebsite($customer->getWebsiteId())
            ->getStoreIds();
        $collection = Mage::getModel('sales/order')->getCollection();
        $collection->addFieldToFilter('customer_id', $customer->getId())
            ->addFieldToFilter('store_id', array('in' => $storeIds))
            ->setPageSize(1)
            ->setOrder('entity_id');

        if ($collection->getSize()) {
            //@codingStandardsIgnoreStart
            return $collection->getFirstItem();
            //@codingStandardsIgnoreEnd
        } else {
            return false;
        }
    }

    /**
     * Get customer last quote id.
     *
     * @param Mage_Customer_Model_Customer $customer
     *
     * @return bool|Varien_Object
     */
    public function getCustomerLastQuoteId(Mage_Customer_Model_Customer $customer)
    {
        $storeIds = Mage::app()->getWebsite($customer->getWebsiteId())
            ->getStoreIds();
        $collection = Mage::getModel('sales/quote')->getCollection();
        $collection->addFieldToFilter('customer_id', $customer->getId())
            ->addFieldToFilter('store_id', array('in' => $storeIds))
            ->setPageSize(1)
            ->setOrder('entity_id');

        if ($collection->getSize()) {
            //@codingStandardsIgnoreStart
            return $collection->getFirstItem();
            //@codingStandardsIgnoreEnd
        } else {
            return false;
        }
    }

    /**
     * Set imported in bulk query.
     *
     * @param $ids
     * @param $modified
     */
    protected function _setImported($ids, $modified = false)
    {
        try {
            $coreResource = Mage::getSingleton('core/resource');
            $write = $coreResource->getConnection('core_write');
            $tableName = $coreResource->getTableName('ddg_automation/order');
            $ids = implode(', ', $ids);
            $now = Mage::getSingleton('core/date')->gmtDate();

            if ($modified) {
                $write->update(
                    $tableName,
                    array(
                        'modified' => new Zend_Db_Expr('null'),
                        'updated_at' => $now
                    ),
                    "order_id IN ($ids)"
                );
            } else {
                $write->update(
                    $tableName,
                    array('email_imported' => 1, 'updated_at' => $now),
                    "order_id IN ($ids)"
                );
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }
}