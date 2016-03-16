<?php

class Dotdigitalgroup_Email_Model_Apiconnector_Customer
{

    public $customer;
    public $customerData;
    public $reviewCollection;

    //enterprise reward
    public $reward;

    public $rewardCustomer;
    public $rewardLastSpent = "";
    public $rewardLastEarned = "";
    public $rewardExpiry = "";

    protected $_mappingHash;

    public $subscriberStatus
        = array(
            Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED   => 'Subscribed',
            Mage_Newsletter_Model_Subscriber::STATUS_NOT_ACTIVE   => 'Not Active',
            Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED => 'Unsubscribed',
            Mage_Newsletter_Model_Subscriber::STATUS_UNCONFIRMED  => 'Unconfirmed'
        );


    /**
     * constructor, mapping hash to map.
     *
     * @param $mappingHash
     */
    public function __construct($mappingHash)
    {
        $this->setMappigHash($mappingHash);
    }

    /**
     * Set key value data.
     *
     * @param $data
     */
    public function setData($data)
    {
        $this->customerData[] = $data;
    }

    /**
     * Set customer data.
     *
     * @param Mage_Customer_Model_Customer $customer
     */
    public function setCustomerData(Mage_Customer_Model_Customer $customer)
    {
        $this->customer = $customer;
        $this->setReviewCollection();
        $website = $customer->getStore()->getWebsite();

        if ($website && Mage::helper('ddg')->isSweetToothToGo($website)) {
            $this->setRewardCustomer($customer);
        }

        foreach ($this->getMappingHash() as $key => $field) {

            /**
             * call user function based on the attribute mapped.
             */
            $function = 'get';
            $exploded = explode('_', $key);
            foreach ($exploded as $one) {
                $function .= ucfirst($one);
            }
            try {
                $value                    = call_user_func(
                    array('self', $function)
                );
                $this->customerData[$key] = $value;
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
    }

    public function setReviewCollection()
    {
        $customerId = $this->customer->getId();
        $collection  = Mage::getModel('review/review')->getCollection()
            ->addCustomerFilter($customerId)
            ->setOrder('review_id', 'DESC');

        $this->reviewCollection = $collection;
    }

    public function getReviewCount()
    {
        return $this->reviewCollection->getSize();
    }

    public function getLastReviewDate()
    {

        if (count($this->reviewCollection)) {
            $this->reviewCollection->getSelect()->limit(1);

            return $this->reviewCollection->getFirstItem()->getCreatedAt();
        }

        return '';
    }

    /**
     * Set reward customer
     *
     * @param Mage_Customer_Model_Customer $customer
     */
    public function setRewardCustomer(Mage_Customer_Model_Customer $customer)
    {
        //get tbt reward customer
        $tbtReward           = Mage::getModel('rewards/customer')
            ->getRewardsCustomer(
                $customer
            );
        $this->rewardCustomer = $tbtReward;

        //get transfers collection from tbt reward. only active and order by last updated.
        $lastTransfers = $tbtReward->getTransfers()
            ->selectOnlyActive()
            ->addOrder(
                'last_update_ts', Varien_Data_Collection::SORT_ORDER_DESC
            );

        $spent = $earn = null;

        foreach ($lastTransfers as $transfer) {
            // if transfer quantity is greater then 0 then this is last points earned date.
            // keep checking until earn is not null
            if (is_null($earn) && $transfer->getQuantity() > 0) {
                $earn = $transfer->getEffectiveStart();
            } else if (is_null($spent) && $transfer->getQuantity() < 0) {
                // id transfer quantity is less then 0 then this is last points spent date.
                // keep checking until spent is not null
                $spent = $transfer->getEffectiveStart();
            }
            // break if both spent and earn are not null (a value has been assigned)
            if ( ! is_null($spent) && ! is_null($earn)) {
                break;
            }
        }

        // if earn is not null (has a value) then assign the value to property
        if ($earn) {
            $this->rewardLastEarned = $earn;
        }
        // if spent is not null (has a value) then assign the value to property
        if ($spent) {
            $this->rewardLastSpent = $spent;
        }

        $tbtExpiry = Mage::getSingleton('rewards/expiry')
            ->getExpiryDate($tbtReward);

        // if there is an expiry (has a value) then assign the value to property
        if ($tbtExpiry) {
            $this->rewardExpiry = $tbtExpiry;
        }
    }

    /**
     * get customer id.
     *
     * @return mixed
     */
    public function getCustomerId()
    {
        return $this->customer->getId();
    }

    /**
     * get first name.
     *
     * @return mixed
     */
    public function getFirstname()
    {
        return $this->customer->getFirstname();
    }

    /**
     * get last name.
     *
     * @return mixed
     */
    public function getLastname()
    {
        return $this->customer->getLastname();
    }

    /**
     * get date of birth.
     *
     * @return mixed
     */
    public function getDob()
    {
        return $this->customer->getDob();
    }

    /**
     * get customer gender.
     *
     * @return bool|string
     */
    public function getGender()
    {
        return $this->_getCustomerGender();
    }

    /**
     * get customer prefix.
     *
     * @return mixed
     */
    public function getPrefix()
    {
        return $this->customer->getPrefix();
    }

    /**
     * get customer suffix.
     *
     * @return mixed
     */
    public function getSuffix()
    {
        return $this->customer->getSuffix();
    }

    /**
     * get website name.
     *
     * @return string
     */
    public function getWebsiteName()
    {
        return $this->_getWebsiteName();
    }

    /**
     * get store name.
     *
     * @return null|string
     */
    public function getStoreName()
    {
        return $this->_getStoreName();
    }

    /**
     * get customer created at date.
     *
     * @return mixed
     */
    public function getCreatedAt()
    {
        return $this->customer->getCreatedAt();
    }

    /**
     * get customer last logged in date.
     *
     * @return mixed
     */
    public function getLastLoggedDate()
    {
        return $this->customer->getLastLoggedDate();
    }

    /**
     * get cutomer group.
     *
     * @return string
     */
    public function getCustomerGroup()
    {
        return $this->_getCustomerGroup();
    }

    /**
     * get billing address line 1.
     *
     * @return string
     */
    public function getBillingAddress1()
    {
        return $this->_getStreet($this->customer->getBillingStreet(), 1);
    }

    /**
     * get billing address line 2.
     *
     * @return string
     */
    public function getBillingAddress2()
    {
        return $this->_getStreet($this->customer->getBillingStreet(), 2);
    }

    /**
     * get billing city.
     *
     * @return mixed
     */
    public function getBillingCity()
    {
        return $this->customer->getBillingCity();
    }

    /**
     * get billing country.
     *
     * @return mixed
     */
    public function getBillingCountry()
    {
        return $this->customer->getBillingCountryCode();
    }

    /**
     * get billing state.
     *
     * @return mixed
     */
    public function getBillingState()
    {
        return $this->customer->getBillingRegion();
    }

    /**
     * get billing postcode.
     *
     * @return mixed
     */
    public function getBillingPostcode()
    {
        return $this->customer->getBillingPostcode();
    }

    /**
     * get billing phone.
     *
     * @return mixed
     */
    public function getBillingTelephone()
    {
        return $this->customer->getBillingTelephone();
    }

    /**
     * get delivery address line 1.
     *
     * @return string
     */
    public function getDeliveryAddress1()
    {
        return $this->_getStreet($this->customer->getShippingStreet(), 1);
    }

    /**
     * get delivery addrss line 2.
     *
     * @return string
     */
    public function getDeliveryAddress2()
    {
        return $this->_getStreet($this->customer->getShippingStreet(), 2);
    }

    /**
     * get delivery city.
     *
     * @return mixed
     */
    public function getDeliveryCity()
    {
        return $this->customer->getShippingCity();
    }

    /**
     * get delivery country.
     *
     * @return mixed
     */
    public function getDeliveryCountry()
    {
        return $this->customer->getShippingCountryCode();
    }

    /**
     * get delivery state.
     *
     * @return mixed
     */
    public function getDeliveryState()
    {
        return $this->customer->getShippingRegion();
    }

    /**
     * get delivery postcode.
     *
     * @return mixed
     */
    public function getDeliveryPostcode()
    {
        return $this->customer->getShippingPostcode();
    }

    /**
     * get delivery phone.
     *
     * @return mixed
     */
    public function getDeliveryTelephone()
    {
        return $this->customer->getShippingTelephone();
    }

    /**
     * get numbser of orders.
     *
     * @return mixed
     */
    public function getNumberOfOrders()
    {
        return $this->customer->getNumberOfOrders();
    }

    /**
     * get average order value.
     *
     * @return mixed
     */
    public function getAverageOrderValue()
    {
        return $this->customer->getAverageOrderValue();
    }

    /**
     * get total spend.
     *
     * @return mixed
     */
    public function getTotalSpend()
    {
        return $this->customer->getTotalSpend();
    }

    /**
     * get last order date.
     *
     * @return mixed
     */
    public function getLastOrderDate()
    {
        return $this->customer->getLastOrderDate();
    }

    /**
     * get last order id.
     *
     * @return mixed
     */
    public function getLastOrderId()
    {
        return $this->customer->getLastOrderId();
    }

    /**
     * get last quote id.
     *
     * @return mixed
     */
    public function getLastQuoteId()
    {
        return $this->customer->getLastQuoteId();
    }

    /**
     * get cutomer id.
     *
     * @return mixed
     */
    public function getId()
    {
        return $this->customer->getId();
    }

    /**
     * get customer title.
     *
     * @return mixed
     */
    public function getTitle()
    {
        return $this->customer->getPrefix();
    }

    /**
     * get total refund value.
     *
     * @return float|int
     */
    public function getTotalRefund()
    {
        $orders        = Mage::getResourceModel('sales/order_collection')
            ->addAttributeToFilter('customer_id', $this->customer->getId());
        $totalRefunded = 0;
        foreach ($orders as $order) {
            $refunded = $order->getTotalRefunded();
            $totalRefunded += $refunded;
        }

        return $totalRefunded;
    }

    /**
     * export to CSV.
     *
     * @return mixed
     */
    public function toCSVArray()
    {
        $result = $this->customerData;

        return $result;
    }

    /**
     * customer gender.
     *
     * @return bool|string
     * @throws Mage_Core_Exception
     */
    protected function _getCustomerGender()
    {
        $genderId = $this->customer->getGender();
        if (is_numeric($genderId)) {
            $gender = Mage::getResourceModel('customer/customer')
                ->getAttribute('gender')
                ->getSource()
                ->getOptionText($genderId);

            return $gender;
        }

        return '';
    }

    protected function _getStreet($street, $line)
    {
        $street = explode("\n", $street);
        if (isset($street[$line - 1])) {
            return $street[$line - 1];
        }

        return '';
    }

    protected function _getWebsiteName()
    {
        $websiteId = $this->customer->getWebsiteId();
        $website   = Mage::app()->getWebsite($websiteId);
        if ($website) {
            return $website->getName();
        }

        return '';
    }

    protected function _getStoreName()
    {
        $storeId = $this->customer->getStoreId();
        $store   = Mage::app()->getStore($storeId);
        if ($store) {
            return $store->getName();
        }

        return '';
    }

    /**
     * @param mixed $mappingHash
     */
    public function setMappingHash($mappingHash)
    {
        $this->_mappingHash = $mappingHash;
    }

    /**
     * @return mixed
     */
    public function getMappingHash()
    {
        return $this->_mappingHash;
    }

    protected function _getCustomerGroup()
    {

        $groupId = $this->customer->getGroupId();
        $group   = Mage::getModel('customer/group')->load($groupId);

        if ($group->getId()) {
            return $group->getCode();
        }

        return '';
    }

    /**
     * mapping hash value.
     *
     * @param $value
     *
     * @return $this
     */
    public function setMappigHash($value)
    {
        $this->_mappingHash = $value;

        return $this;
    }

    public function getRewardReferralUrl()
    {
        if (Mage::helper('ddg')->isSweetToothToGo(
            $this->customer->getStore()->getWebsite()
        )
        ) {
            return (string)Mage::helper('rewardsref/url')->getUrl(
                $this->customer
            );
        }

        return '';
    }

    public function getRewardPointBalance()
    {
        return $this->cleanString($this->rewardCustomer->getPointsSummary());
    }

    public function getRewardPointPending()
    {
        return $this->cleanString(
            $this->rewardCustomer->getPendingPointsSummary()
        );
    }

    public function getRewardPointPendingTime()
    {
        return $this->cleanString(
            $this->rewardCustomer->getPendingTimePointsSummary()
        );
    }

    public function getRewardPointOnHold()
    {
        return $this->cleanString(
            $this->rewardCustomer->getOnHoldPointsSummary()
        );
    }

    public function getRewardPointExpiration()
    {
        if ($this->rewardExpiry != "") {
            return Mage::getModel('core/date')->date(
                'Y/m/d', strtotime($this->rewardExpiry)
            );
        }

        return $this->rewardExpiry;
    }

    public function getRewardPointLastSpent()
    {
        return $this->rewardLastSpent;
    }

    public function getRewardPointLastEarn()
    {
        return $this->rewardLastEarned;
    }

    public function cleanString($string)
    {
        $cleanedString = preg_replace("/[^0-9]/", "", $string);
        if ($cleanedString != "") {
            return (int)number_format($cleanedString, 0, '.', '');
        }

        return 0;
    }

    public function getSubscriberStatus()
    {
        $subscriber = Mage::getModel('newsletter/subscriber')->loadByCustomer(
            $this->customer
        );
        if ($subscriber->getCustomerId()) {
            return $this->subscriberStatus[$subscriber->getSubscriberStatus()];
        }
    }

    /**
     * Reward points balance.
     *
     * @return int
     */
    public function getRewardPoints()
    {
        if ( ! $this->reward) {
            $this->_setReward();
        }

        if ($this->reward !== true) {
            return $this->reward->getPointsBalance();
        }

        return '';
    }

    /**
     * Currency amount points.
     *
     * @return mixed
     */
    public function getRewardAmount()
    {
        if ( ! $this->reward) {
            $this->_setReward();
        }

        if ($this->reward !== true) {
            return $this->reward->getCurrencyAmount();
        }

        return '';
    }

    /**
     * Expiration date to use the points.
     *
     * @return string
     */
    public function getExpirationDate()
    {
        //set reward for later use
        if ( ! $this->reward) {
            $this->_setReward();
        }

        if ($this->reward !== true) {
            $expiredAt = $this->reward->getExpirationDate();

            if ($expiredAt) {
                $date = Mage::helper('core')->formatDate(
                    $expiredAt, 'short', true
                );
            } else {
                $date = '';
            }

            return $date;
        }

        return '';
    }


    protected function _setReward()
    {
        if (Mage::getModel('enterprise_reward/reward_history')) {
            $collection = Mage::getModel('enterprise_reward/reward_history')
                ->getCollection()
                ->addCustomerFilter($this->customer->getId())
                ->addWebsiteFilter($this->customer->getWebsiteId())
                ->setExpiryConfig(
                    Mage::helper('enterprise_reward')->getExpiryConfig()
                )
                ->addExpirationDate($this->customer->getWebsiteId())
                ->skipExpiredDuplicates()
                ->setDefaultOrder();

            $item = $collection->setPageSize(1)->setCurPage(1)->getFirstItem();

            $this->reward = $item;
        } else {
            $this->reward = true;
        }
    }


    /**
     * Customer segments id.
     *
     * @return string
     */
    public function getCustomerSegments()
    {
        $collection = Mage::getModel('ddg_automation/contact')->getCollection()
            ->addFieldToFilter('customer_id', $this->getCustomerId())
            ->addFieldToFilter('website_id', $this->customer->getWebsiteId());

        $item = $collection->setPageSize(1)->setCurPage(1)->getFirstItem();

        if ($item) {
            return $item->getSegmentIds();
        }

        return '';
    }


    /**
     * Last used reward points.
     *
     * @return mixed
     */
    public function getLastUsedDate()
    {
        if (Mage::getModel('enterprise_reward/reward_history')) {
            //last used from the reward history based on the points delta used
            $collection = Mage::getModel('enterprise_reward/reward_history')
                ->getCollection()
                ->addCustomerFilter($this->customer->getId())
                ->addWebsiteFilter($this->customer->getWebsiteId())
                ->addFieldToFilter('points_delta', array('lt' => 0))
                ->setDefaultOrder();

            $item     = $collection->setPageSize(1)->setCurPage(1)
                ->getFirstItem();
            $lastUsed = $item->getCreatedAt();

            //for any valid date
            if ($lastUsed) {
                return $date = Mage::helper('core')->formatDate(
                    $lastUsed, 'short', true
                );
            }
        }

        return '';
    }


    /**
     * get most purchased category
     *
     * @return string
     */
    public function getMostPurCategory()
    {
        $id = $this->customer->getMostCategoryId();
        if ($id) {
            return Mage::getModel('catalog/category')
                ->load($id)
                ->setStoreId($this->customer->getStoreId())
                ->getName();
        }

        return "";
    }

    /**
     * get most purchased brand
     *
     * @return string
     */
    public function getMostPurBrand()
    {
        $brand = $this->customer->getMostBrand();
        if ($brand) {
            return $brand;
        }

        return "";
    }

    /**
     * get most frequent day of purchase
     *
     * @return string
     */
    public function getMostFreqPurDay()
    {
        $day = $this->customer->getWeekDay();
        if ($day) {
            return $day;
        }

        return "";
    }

    /**
     * get most frequent month of purchase
     *
     * @return string
     */
    public function getMostFreqPurMon()
    {
        $month = $this->customer->getMonthDay();
        if ($month) {
            return $month;
        }

        return "";
    }

    /**
     * get first purchased category
     *
     * @return string
     */
    public function getFirstCategoryPur()
    {
        $id = $this->customer->getFirstCategoryId();
        if ($id) {
            return Mage::getModel('catalog/category')
                ->load($id)
                ->setStoreId($this->customer->getStoreId())
                ->getName();
        }

        return "";
    }

    /**
     * get last purchased category
     *
     * @return string
     */
    public function getLastCategoryPur()
    {
        $id = $this->customer->getLastCategoryId();
        if ($id) {
            return Mage::getModel('catalog/category')
                ->setStoreId($this->customer->getStoreId())
                ->load($id)
                ->getName();
        }

        return "";
    }

    /**
     * get first purchased brand
     *
     * @return string
     */
    public function getFirstBrandPur()
    {
        $id = $this->customer->getProductIdForFirstBrand();

        return $this->_getBrandValue($id);
    }

    /**
     * get last purchased brand
     *
     * @return string
     */
    public function getLastBrandPur()
    {
        $id = $this->customer->getProductIdForLastBrand();

        return $this->_getBrandValue($id);
    }

    protected function _getBrandValue($id)
    {
        $attribute = Mage::helper('ddg')->getWebsiteConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_DATA_FIELDS_BRAND_ATTRIBUTE,
            $this->customer->getWebsiteId()
        );
        if ($id && $attribute) {
            $brand = Mage::getModel('catalog/product')
                ->setStoreId($this->customer->getStoreId())
                ->load($id)
                ->getAttributeText($attribute);
            if ($brand) {
                return $brand;
            }
        }

        return "";
    }

    /**
     * get last increment id
     *
     * @return mixed
     */
    public function getLastIncrementId()
    {
        return $this->customer->getLastIncrementId();
    }
}