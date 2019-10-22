<?php

class Dotdigitalgroup_Email_Model_Apiconnector_Customer
{
    /**
     * @var
     */
    public $object;

    /**
     * @var
     */
    public $objectData;

    /**
     * @var
     */
    public $reviewCollection;

    /**
     * Enterprise reward data [enterprise_reward_history]
     *
     * @var
     */
    public $rewardDataFromHistory;

    /**
     * @var
     */
    public $rewardCustomer;

    /**
     * @var string
     */
    public $rewardLastSpent = "";

    /**
     * @var string
     */
    public $rewardLastEarned = "";

    /**
     * @var string
     */
    public $rewardExpiry = "";

    /**
     * @var
     */
    public $mappingHash;

    /**
     * @var int
     */
    public $storeId;

    /**
     * @var int
     */
    public $websiteId;

    /**
     * @var array
     */
    public $subscriberStatus = array(
            Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED   => 'Subscribed',
            Mage_Newsletter_Model_Subscriber::STATUS_NOT_ACTIVE   => 'Not Active',
            Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED => 'Unsubscribed',
            Mage_Newsletter_Model_Subscriber::STATUS_UNCONFIRMED  => 'Unconfirmed'
        );


    /**
     * Constructor, mapping hash to map.
     *
     * @param $mappingHash
     */
    public function __construct($mappingHash)
    {
        $this->setMappingHash($mappingHash);
    }

    /**
     * Set key value data.
     *
     * @param $data
     */
    public function setData($data)
    {
        $this->objectData[] = $data;
    }

    /**
     * Set customer data.
     *
     * @param Mage_Customer_Model_Customer $customer
     */
    public function setCustomerData(Mage_Customer_Model_Customer $customer)
    {
        $this->object = $customer;
        $this->setReviewCollection();
        $this->websiteId = $customer->getStore()->getWebsiteId();
        $this->storeId = $customer->getStore()->getId();

        if ($this->websiteId && Mage::helper('ddg')->isSweetToothToGo($this->websiteId)) {
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
                //@codingStandardsIgnoreStart
                $value = call_user_func(array('self', $function));
                //@codingStandardsIgnoreEnd
                $this->objectData[$key] = $value;
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
    }

    /**
     * Customer review.
     */
    public function setReviewCollection()
    {
        if (!Mage::helper('ddg/moduleChecker')->isReviewModuleAvailable()) {
            return;
        }

        $customerId = $this->object->getId();
        $collection  = Mage::getModel('review/review')->getCollection()
            ->addCustomerFilter($customerId)
            ->setOrder('review_id', 'DESC');

        $this->reviewCollection = $collection;
    }

    /**
     * @return int
     */

    public function getReviewCount()
    {
        return (empty($this->reviewCollection)) ? 0 : $this->reviewCollection->getSize();
    }

    /**
     * @return string
     */
    public function getLastReviewDate()
    {
        if (!empty($this->reviewCollection)) {
            //@codingStandardsIgnoreStart
            $this->reviewCollection->getSelect()->limit(1);
            return $this->reviewCollection->getFirstItem()->getCreatedAt();
            //@codingStandardsIgnoreEnd
        }

        return '';
    }

    /**
     * Set reward customer.
     *
     * @param Mage_Customer_Model_Customer $customer
     */
    public function setRewardCustomer(Mage_Customer_Model_Customer $customer)
    {
        //get tbt reward customer
        $tbtReward           = Mage::getModel('rewards/customer')
            ->getRewardsCustomer($customer);

        if (! is_object($tbtReward)) {
            return;
        }

        $this->rewardCustomer = $tbtReward;

        //get transfers collection from tbt reward. only active and order by last updated.
        $lastTransfers = $tbtReward->getTransfers()
            ->selectOnlyActive()
            ->addOrder(
                'updated_at',
                Varien_Data_Collection::SORT_ORDER_DESC
            );

        $spent = $earn = null;

        foreach ($lastTransfers as $transfer) {
            // if transfer quantity is greater then 0 then this is last points earned date.
            // keep checking until earn is not null
            if ($earn == null && $transfer->getQuantity() > 0) {
                $earn = $transfer->getEffectiveStart();
            } elseif ($spent == null && $transfer->getQuantity() < 0) {
                // id transfer quantity is less then 0 then this is last points spent date.
                // keep checking until spent is not null
                $spent = $transfer->getEffectiveStart();
            }

            // break if both spent and earn are not null (a value has been assigned)
            if ($spent !== null && !$earn !== null) {
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
     * Get customer id.
     *
     * @return mixed
     */
    public function getCustomerId()
    {
        return $this->object->getId();
    }

    /**
     * Get first name.
     *
     * @return mixed
     */
    public function getFirstname()
    {
        return $this->object->getFirstname();
    }

    /**
     * Get last name.
     *
     * @return mixed
     */
    public function getLastname()
    {
        return $this->object->getLastname();
    }

    /**
     * Get date of birth.
     *
     * @return mixed
     */
    public function getDob()
    {
        return $this->object->getDob();
    }

    /**
     * Get customer gender.
     *
     * @return bool|string
     */
    public function getGender()
    {
        return $this->_getCustomerGender();
    }

    /**
     * Get customer prefix.
     *
     * @return mixed
     */
    public function getPrefix()
    {
        return $this->object->getPrefix();
    }

    /**
     * Get customer suffix.
     *
     * @return mixed
     */
    public function getSuffix()
    {
        return $this->object->getSuffix();
    }

    /**
     * Get website name.
     *
     * @return string
     */
    public function getWebsiteName()
    {
        return $this->_getWebsiteName();
    }

    /**
     * Get store name.
     *
     * @return null|string
     */
    public function getStoreName()
    {
        return $this->_getStoreName();
    }

    /**
     * Get customer created at date.
     *
     * @return mixed
     */
    public function getCreatedAt()
    {
        return $this->object->getCreatedAt();
    }

    /**
     * Get customer last logged in date.
     *
     * @return mixed
     */
    public function getLastLoggedDate()
    {
        return $this->object->getLastLoggedDate();
    }

    /**
     * Get customer group.
     *
     * @return string
     */
    public function getCustomerGroup()
    {
        return $this->_getCustomerGroup();
    }

    /**
     * Get billing address line 1.
     *
     * @return string
     */
    public function getBillingAddress1()
    {
        return $this->_getStreet($this->object->getBillingStreet(), 1);
    }

    /**
     * Get billing address line 2.
     *
     * @return string
     */
    public function getBillingAddress2()
    {
        return $this->_getStreet($this->object->getBillingStreet(), 2);
    }

    /**
     * Get billing city.
     *
     * @return mixed
     */
    public function getBillingCity()
    {
        return $this->object->getBillingCity();
    }

    /**
     * Get billing country.
     *
     * @return mixed
     */
    public function getBillingCountry()
    {
        return $this->object->getBillingCountryCode();
    }

    /**
     * Get billing state.
     *
     * @return mixed
     */
    public function getBillingState()
    {
        return $this->object->getBillingRegion();
    }

    /**
     * Get billing postcode.
     *
     * @return mixed
     */
    public function getBillingPostcode()
    {
        return $this->object->getBillingPostcode();
    }

    /**
     * Get billing phone.
     *
     * @return mixed
     */
    public function getBillingTelephone()
    {
        return $this->object->getBillingTelephone();
    }

    /**
     * Get delivery address line 1.
     *
     * @return string
     */
    public function getDeliveryAddress1()
    {
        return $this->_getStreet($this->object->getShippingStreet(), 1);
    }

    /**
     * Get delivery address line 2.
     *
     * @return string
     */
    public function getDeliveryAddress2()
    {
        return $this->_getStreet($this->object->getShippingStreet(), 2);
    }

    /**
     * Get delivery city.
     *
     * @return mixed
     */
    public function getDeliveryCity()
    {
        return $this->object->getShippingCity();
    }

    /**
     * Get delivery country.
     *
     * @return mixed
     */
    public function getDeliveryCountry()
    {
        return $this->object->getShippingCountryCode();
    }

    /**
     * Get delivery state.
     *
     * @return mixed
     */
    public function getDeliveryState()
    {
        return $this->object->getShippingRegion();
    }

    /**
     * Get delivery postcode.
     *
     * @return mixed
     */
    public function getDeliveryPostcode()
    {
        return $this->object->getShippingPostcode();
    }

    /**
     * Get delivery phone.
     *
     * @return mixed
     */
    public function getDeliveryTelephone()
    {
        return $this->object->getShippingTelephone();
    }

    /**
     * Get number of orders.
     *
     * @return mixed
     */
    public function getNumberOfOrders()
    {
        return $this->object->getNumberOfOrders();
    }

    /**
     * Get average order value.
     *
     * @return mixed
     */
    public function getAverageOrderValue()
    {
        return (float)number_format(
            $this->object->getAverageOrderValue(),
            2,
            '.',
            ''
        );
    }

    /**
     * Get total spend.
     *
     * @return mixed
     */
    public function getTotalSpend()
    {
        return (float)number_format(
            $this->object->getTotalSpend(),
            2,
            '.',
            ''
        );
    }

    /**
     * Get last order date.
     *
     * @return mixed
     */
    public function getLastOrderDate()
    {
        return $this->object->getLastOrderDate();
    }

    /**
     * Get last order id.
     *
     * @return mixed
     */
    public function getLastOrderId()
    {
        return $this->object->getLastOrderId();
    }

    /**
     * Get last quote id.
     *
     * @return mixed
     */
    public function getLastQuoteId()
    {
        return $this->object->getLastQuoteId();
    }

    /**
     * Get customer id.
     *
     * @return mixed
     */
    public function getId()
    {
        return $this->object->getId();
    }

    /**
     * Get customer title.
     *
     * @return mixed
     */
    public function getTitle()
    {
        return $this->object->getPrefix();
    }

    /**
     * Get total refund value.
     *
     * @return float|int
     */
    public function getTotalRefund()
    {
        $orders        = Mage::getResourceModel('sales/order_collection')
            ->addAttributeToFilter('customer_id', $this->object->getId());
        $totalRefunded = 0;
        foreach ($orders as $order) {
            $refunded = $order->getTotalRefunded();
            $totalRefunded += $refunded;
        }

        return (float)number_format(
            $totalRefunded,
            2,
            '.',
            ''
        );
    }

    /**
     * Export to CSV.
     *
     * @return mixed
     */
    public function toCSVArray()
    {
        $result = $this->objectData;

        return $result;
    }

    /**
     * Customer gender.
     *
     * @return bool|string
     * @throws Mage_Core_Exception
     */
    protected function _getCustomerGender()
    {
        $genderId = $this->object->getGender();
        if (is_numeric($genderId)) {
            $gender = Mage::getResourceModel('customer/customer')
                ->getAttribute('gender')
                ->getSource()
                ->getOptionText($genderId);

            return $gender;
        }

        return '';
    }

    /**
     * @param $street
     * @param $line
     * @return string
     */
    protected function _getStreet($street, $line)
    {
        $street = explode("\n", $street);
        if (isset($street[$line - 1])) {
            return $street[$line - 1];
        }

        return '';
    }

    /**
     * @return string
     */
    protected function _getWebsiteName()
    {
        if ($this->websiteId) {
            $website = Mage::app()->getWebsite($this->websiteId);
            return $website->getName();
        }

        return '';
    }

    /**
     * @return null|string
     */
    protected function _getStoreName()
    {
        if ($this->storeId) {
            $store = Mage::app()->getStore($this->storeId);
            return $store->getName();
        }

        return '';
    }

    /**
     * @param mixed $mappingHash
     */
    public function setMappingHash($mappingHash)
    {
        $this->mappingHash = $mappingHash;
    }

    /**
     * @return mixed
     */
    public function getMappingHash()
    {
        return $this->mappingHash;
    }

    /**
     * @return string
     */
    protected function _getCustomerGroup()
    {
        $groupId = $this->object->getGroupId();
        $group   = Mage::getModel('customer/group')->load($groupId);

        if ($group->getId()) {
            return $group->getCode();
        }

        return '';
    }

    /**
     * @return string
     */
    public function getRewardReferralUrl()
    {
        if (Mage::helper('ddg')->isSweetToothToGo($this->websiteId)
        ) {
            return (string)Mage::helper('rewardsref/url')->getUrl(
                $this->object
            );
        }

        return '';
    }

    /**
     * @return int
     */
    public function getRewardPointBalance()
    {
        if (! isset($this->rewardCustomer)) {
            return 0;
        }
        return $this->cleanString($this->rewardCustomer->getPointsSummary());
    }

    /**
     * @return int
     */
    public function getRewardPointPending()
    {
        if (! isset($this->rewardCustomer)) {
            return 0;
        }
        return $this->cleanString($this->rewardCustomer->getPendingPointsSummary());
    }

    /**
     * @return int
     */
    public function getRewardPointPendingTime()
    {
        if (! isset($this->rewardCustomer)) {
            return 0;
        }
        return $this->cleanString($this->rewardCustomer->getPendingTimePointsSummary());
    }

    /**
     * @return int
     */
    public function getRewardPointOnHold()
    {
        if (! isset($this->rewardCustomer)) {
            return 0;
        }
        return $this->cleanString($this->rewardCustomer->getOnHoldPointsSummary());
    }

    /**
     * @return string
     */
    public function getRewardPointExpiration()
    {
        if ($this->rewardExpiry != "") {
            //@codingStandardsIgnoreStart
            return Mage::getModel('core/date')->date(
                'Y/m/d',
                strtotime($this->rewardExpiry)
            );
            //@codingStandardsIgnoreEnd
        }

        return $this->rewardExpiry;
    }

    /**
     * @return string
     */
    public function getRewardPointLastSpent()
    {
        return $this->rewardLastSpent;
    }

    /**
     * @return string
     */
    public function getRewardPointLastEarn()
    {
        return $this->rewardLastEarned;
    }

    /**
     * @param $string
     * @return int
     */
    public function cleanString($string)
    {
        $cleanedString = preg_replace("/[^0-9]/", "", $string);
        if ($cleanedString != "") {
            return (int)number_format($cleanedString, 0, '.', '');
        }

        return 0;
    }

    /**
     * @return mixed
     */
    public function getSubscriberStatus()
    {
        $subscriber = Mage::getModel('newsletter/subscriber')->loadByCustomer(
            $this->object
        );
        if ($subscriber->getCustomerId()) {
            return $this->subscriberStatus[$subscriber->getSubscriberStatus()];
        }
    }

    /**
     * Get reward points balance from enterprise_reward table.
     * enterprise_reward is a more reliable source of customer points balance than enterprise_reward_history.
     * For example, history points balance is not updated after reward expiry.
     *
     * @return string
     */
    public function getRewardPoints()
    {
        if (Mage::getModel('enterprise_reward/reward') && $this->websiteId > 0) {
            $pointsBalance = Mage::getModel('enterprise_reward/reward')
                ->setCustomer($this->object)
                ->setWebsiteId($this->websiteId)
                ->loadByCustomer()
                ->getPointsBalance();

            return $pointsBalance;
        }
    }

    /**
     * Currency amount points.
     *
     * @return mixed
     */
    public function getRewardAmount()
    {
        if (!$this->rewardDataFromHistory) {
            $this->_setRewardDataFromHistory();
        }

        if ($this->rewardDataFromHistory !== true) {
            return $this->rewardDataFromHistory->getCurrencyAmount();
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
        if (!$this->rewardDataFromHistory) {
            $this->_setRewardDataFromHistory();
        }

        if ($this->rewardDataFromHistory !== true) {
            $expiredAt = $this->rewardDataFromHistory->getExpirationDate();

            if ($expiredAt) {
                $date = Mage::helper('core')->formatDate(
                    $expiredAt,
                    'short',
                    true
                );
            } else {
                $date = '';
            }

            return $date;
        }

        return '';
    }

    /**
     * Retrieve the most recent row from the enterprise_reward_history table, by customer and website.
     */
    protected function _setRewardDataFromHistory()
    {
        if (Mage::getModel('enterprise_reward/reward_history') && $this->websiteId > 0) {
            $collection = Mage::getModel('enterprise_reward/reward_history')
                ->getCollection()
                ->addCustomerFilter($this->object->getId())
                ->addWebsiteFilter($this->websiteId)
                ->setExpiryConfig(Mage::helper('enterprise_reward')->getExpiryConfig())
                ->addExpirationDate($this->websiteId)
                ->skipExpiredDuplicates()
                ->setDefaultOrder();

            //@codingStandardsIgnoreStart
            $item = $collection->setPageSize(1)->setCurPage(1)->getFirstItem();
            //@codingStandardsIgnoreEnd

            $this->rewardDataFromHistory = $item;
        } else {
            $this->rewardDataFromHistory = true;
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
            ->addFieldToFilter('website_id', $this->websiteId);

        //@codingStandardsIgnoreStart
        $item = $collection->setPageSize(1)->setCurPage(1)->getFirstItem();
        //@codingStandardsIgnoreEnd

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
                ->addCustomerFilter($this->object->getId())
                ->addWebsiteFilter($this->websiteId)
                ->addFieldToFilter('points_delta', array('lt' => 0))
                ->setDefaultOrder();

            //@codingStandardsIgnoreStart
            $item     = $collection->setPageSize(1)->setCurPage(1)
                ->getFirstItem();
            //@codingStandardsIgnoreEnd
            $lastUsed = $item->getCreatedAt();

            //for any valid date
            if ($lastUsed) {
                return $date = Mage::helper('core')->formatDate(
                    $lastUsed,
                    'short',
                    true
                );
            }
        }

        return '';
    }

    /**
     * Get most purchased category
     *
     * @return string
     */
    public function getMostPurCategory()
    {
        $categoryId = $this->object->getMostCategoryId();
        if ($categoryId) {
            return Mage::getModel('catalog/category')
                ->setStoreId($this->storeId)
                ->load($categoryId)
                ->getName();
        }

        return "";
    }

    /**
     * Get most purchased brand.
     *
     * @return string
     */
    public function getMostPurBrand()
    {
        $optionId = $this->object->getMostBrand();
        $brandAttribute = Mage::helper('ddg')->getWebsiteConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_DATA_FIELDS_BRAND_ATTRIBUTE,
            $this->websiteId
        );

        if ($optionId && $brandAttribute) {
            $attribute = Mage::getSingleton('eav/config')->getAttribute(
                Mage_Catalog_Model_Product::ENTITY,
                $brandAttribute
            );

            if ($attribute instanceof Mage_Eav_Model_Entity_Attribute_Abstract) {
                $value = $attribute->setStoreId($this->storeId)
                    ->getSource()
                    ->getOptionText($optionId);

                if ($value) {
                    return $value;
                }
            }
        }

        return "";
    }

    /**
     * Get most frequent day of purchase.
     *
     * @return string
     */
    public function getMostFreqPurDay()
    {
        $weekDay = $this->object->getWeekDay();
        if ($weekDay) {
            return $weekDay;
        }

        return "";
    }

    /**
     * Get most frequent month of purchase.
     *
     * @return string
     */
    public function getMostFreqPurMon()
    {
        $monthDay = $this->object->getMonthDay();
        if ($monthDay) {
            return $monthDay;
        }

        return "";
    }

    /**
     * Get first purchased category.
     *
     * @return string
     */
    public function getFirstCategoryPur()
    {
        $orderId = $this->object->getFirstOrderId();
        if ($orderId) {
            /** @var Mage_Sales_Model_Order $order */
            $order = Mage::getModel('sales/order')->load($orderId);
            $categoryIds = $this->getCategoriesFromOrderItems($order->getAllItems());
            return $this->getCategoryNames($categoryIds);
        }

        return "";
    }

    /**
     * @param $orderItems
     * @return array
     */
    public function getCategoriesFromOrderItems($orderItems)
    {
        $catIds = array();
        //categories from all products
        /** @var Mage_Sales_Model_Order_Item $item */
        foreach ($orderItems as $item) {
            $product = $item->getProduct();
            $categoryIds = $product->getCategoryIds();
            if (count($categoryIds)) {
                $catIds = array_unique(array_merge($catIds, $categoryIds));
            }
        }

        return $catIds;
    }

    /**
     * @param $categoryId
     * @return string
     */
    protected function getCategoryValue($categoryId)
    {
        if ($categoryId) {
            $category = Mage::getModel('catalog/category')
                ->setStoreId($this->storeId)
                ->load($categoryId);
            return $category->getName();
        }

        return '';
    }

    /**
     * @param $categoryIds
     * @return string
     */
    public function getCategoryNames($categoryIds)
    {
        $names = array();
        foreach ($categoryIds as $id) {
            $categoryValue = $this->getCategoryValue($id);
            $names[$categoryValue] = $categoryValue;
        }
        //comma separated category names
        if (count($names)) {
            return implode(',', $names);
        }

        return '';
    }

    /**
     * Get last purchased category.
     *
     * @return string
     */
    public function getLastCategoryPur()
    {
        $orderId = $this->object->getLastOrderId();
        if ($orderId) {
            /** @var Mage_Sales_Model_Order $order */
            $order = Mage::getModel('sales/order')->load($orderId);
            $categoryIds = $this->getCategoriesFromOrderItems($order->getAllItems());
            return $this->getCategoryNames($categoryIds);
        }

        return "";
    }

    /**
     * Get first purchased brand.
     *
     * @return string
     */
    public function getFirstBrandPur()
    {
        $id = $this->object->getProductIdForFirstBrand();

        return $this->_getBrandValue($id);
    }

    /**
     * Get last purchased brand.
     *
     * @return string
     */
    public function getLastBrandPur()
    {
        $id = $this->object->getProductIdForLastBrand();

        return $this->_getBrandValue($id);
    }

    /**
     * @param $id
     * @return string
     */
    protected function _getBrandValue($id)
    {
        $attributeCode = Mage::helper('ddg')->getWebsiteConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_DATA_FIELDS_BRAND_ATTRIBUTE,
            $this->websiteId
        );

        if ($id && $attributeCode) {
            /** @var Mage_Catalog_Model_Product $product */
            $product = Mage::getModel('catalog/product')
                ->setStoreId($this->storeId)
                ->load($id);

            $attribute = $product->getResource()->getAttribute($attributeCode);
            $value = null;

            if ($attribute instanceof Mage_Catalog_Model_Resource_Eav_Attribute) {
                $value = $attribute->setStoreId($this->storeId)
                    ->getSource()
                    ->getOptionText($product->getData($attributeCode));
            }

            if ($value) {
                return $value;
            }
        }

        return "";
    }

    /**
     * Get last increment id.
     *
     * @return mixed
     */
    public function getLastIncrementId()
    {
        return $this->object->getLastIncrementId();
    }

    /**
     * Get billing company name.
     *
     * @return mixed
     */
    public function getBillingCompany()
    {
        return $this->object->getBillingCompany();
    }

    /**
     * Get shipping company name.
     *
     * @return mixed
     */
    public function getDeliveryCompany()
    {
        return $this->object->getShippingCompany();
    }
}
