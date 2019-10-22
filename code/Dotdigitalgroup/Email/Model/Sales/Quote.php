<?php

/**
 * Class Dotdigitalgroup_Email_Model_Sales_Quote
 * @codingStandardsIgnoreStart
 */
class Dotdigitalgroup_Email_Model_Sales_Quote
{
    //customer
    const XML_PATH_LOSTBASKET_CUSTOMER_ENABLED_1 = 'connector_lost_baskets/customers/enabled_1';
    const XML_PATH_LOSTBASKET_CUSTOMER_ENABLED_2 = 'connector_lost_baskets/customers/enabled_2';
    const XML_PATH_LOSTBASKET_CUSTOMER_ENABLED_3 = 'connector_lost_baskets/customers/enabled_3';
    const XML_PATH_LOSTBASKET_CUSTOMER_INTERVAL_1 = 'connector_lost_baskets/customers/send_after_1';
    const XML_PATH_LOSTBASKET_CUSTOMER_INTERVAL_2 = 'connector_lost_baskets/customers/send_after_2';
    const XML_PATH_LOSTBASKET_CUSTOMER_INTERVAL_3 = 'connector_lost_baskets/customers/send_after_3';
    const XML_PATH_LOSTBASKET_CUSTOMER_CAMPAIGN_1 = 'connector_lost_baskets/customers/campaign_1';
    const XML_PATH_LOSTBASKET_CUSTOMER_CAMPAIGN_2 = 'connector_lost_baskets/customers/campaign_2';
    const XML_PATH_LOSTBASKET_CUSTOMER_CAMPAIGN_3 = 'connector_lost_baskets/customers/campaign_3';

    //guest
    const XML_PATH_LOSTBASKET_GUEST_ENABLED_1 = 'connector_lost_baskets/guests/enabled_1';
    const XML_PATH_LOSTBASKET_GUEST_ENABLED_2 = 'connector_lost_baskets/guests/enabled_2';
    const XML_PATH_LOSTBASKET_GUEST_ENABLED_3 = 'connector_lost_baskets/guests/enabled_3';
    const XML_PATH_LOSTBASKET_GUEST_INTERVAL_1 = 'connector_lost_baskets/guests/send_after_1';
    const XML_PATH_LOSTBASKET_GUEST_INTERVAL_2 = 'connector_lost_baskets/guests/send_after_2';
    const XML_PATH_LOSTBASKET_GUEST_INTERVAL_3 = 'connector_lost_baskets/guests/send_after_3';
    const XML_PATH_LOSTBASKET_GUEST_CAMPAIGN_1 = 'connector_lost_baskets/guests/campaign_1';
    const XML_PATH_LOSTBASKET_GUEST_CAMPAIGN_2 = 'connector_lost_baskets/guests/campaign_2';
    const XML_PATH_LOSTBASKET_GUEST_CAMPAIGN_3 = 'connector_lost_baskets/guests/campaign_3';

    const CUSTOMER_LOST_BASKET_ONE = 1;
    const CUSTOMER_LOST_BASKET_TWO = 2;
    const CUSTOMER_LOST_BASKET_THREE = 3;

    const GUEST_LOST_BASKET_ONE = 1;
    const GUEST_LOST_BASKET_TWO = 2;
    const GUEST_LOST_BASKET_THREE = 3;

    /**
     * @var Zend_Locale
     */
    private $locale;

    /**
     *
     */
    public function processAbandonedCarts()
    {
        $this->locale = Mage::app()->getLocale()->getLocale();
        foreach (Mage::app()->getStores() as $store) {
            $storeId = $store->getStoreId();
            $websiteId = $store->getWebsiteId();
            $secondCustomerEnabled = $this->getLostBasketCustomerEnabled(self::CUSTOMER_LOST_BASKET_TWO, $storeId);
            $thirdCustomerEnabled = $this->getLostBasketCustomerEnabled(self::CUSTOMER_LOST_BASKET_THREE, $storeId);
            $secondGuestEnabled = $this->getLostBasketGuestEnabled(self::GUEST_LOST_BASKET_TWO, $storeId);
            $thirdGuestEnabled = $this->getLostBasketGuestEnabled(self::GUEST_LOST_BASKET_THREE, $storeId);
            /**
             * Customer.
             */
            if ($this->getLostBasketCustomerEnabled(self::CUSTOMER_LOST_BASKET_ONE, $storeId) ||
                $secondCustomerEnabled ||
                $thirdCustomerEnabled
            ) {
                $this->processFirstCustomerAC($storeId, $websiteId);
            }
            //second customer
            if ($secondCustomerEnabled){
                $this->processExistingAC(
                    $this->getLostBasketCustomerCampaignId(self::CUSTOMER_LOST_BASKET_TWO, $storeId),
                    $storeId,
                    $websiteId,
                    self::CUSTOMER_LOST_BASKET_TWO
                );
            }
            //third customer
            if ($thirdCustomerEnabled){
                $this->processExistingAC(
                    $this->getLostBasketCustomerCampaignId(self::CUSTOMER_LOST_BASKET_THREE, $storeId),
                    $storeId,
                    $websiteId,
                    self::CUSTOMER_LOST_BASKET_THREE
                );
            }

            /**
             * Guest.
             */
            if ($this->getLostBasketGuestEnabled(self::GUEST_LOST_BASKET_ONE, $storeId) ||
                $secondGuestEnabled ||
                $thirdGuestEnabled
            ) {
                $this->proccessFirstGuestAC($storeId, $websiteId);
            }
            //second guest
            if ($secondGuestEnabled){
                $this->processExistingAC(
                    $this->getLostBasketGuestCampaignId(self::GUEST_LOST_BASKET_TWO, $storeId),
                    $storeId,
                    $websiteId,
                    self::GUEST_LOST_BASKET_TWO,
                    true
                );
            }
            //third guest
            if ($thirdGuestEnabled) {
                $this->processExistingAC(
                    $this->getLostBasketGuestCampaignId(self::GUEST_LOST_BASKET_THREE, $storeId),
                    $storeId,
                    $websiteId,
                    self::GUEST_LOST_BASKET_THREE,
                    true
                );
            }
        }
    }

    /**
     * @param $num
     * @param $storeId
     * @return null|string
     */
    protected function getLostBasketCustomerCampaignId($num, $storeId)
    {
        $store = Mage::app()->getStore($storeId);

        return $store->getConfig(
            constant('self::XML_PATH_LOSTBASKET_CUSTOMER_CAMPAIGN_' . $num)
        );
    }

    /**
     * @param $num
     * @param $storeId
     * @return null|string
     */
    protected function getLostBasketGuestCampaignId($num, $storeId)
    {
        $store = Mage::app()->getStore($storeId);

        return $store->getConfig(
            constant('self::XML_PATH_LOSTBASKET_GUEST_CAMPAIGN_' . $num)
        );
    }

    /**
     * @param $num
     * @param $storeId
     * @return null|string
     */
    protected function getLostBasketCustomerInterval($num, $storeId)
    {
        $store = Mage::app()->getstore($storeId);

        return $store->getConfig(
            constant('self::XML_PATH_LOSTBASKET_CUSTOMER_INTERVAL_' . $num)
        );
    }

    /**
     * @param $num
     * @param $storeId
     * @return null|string
     */
    protected function getLostBasketGuestInterval($num, $storeId)
    {
        $store = Mage::app()->getStore($storeId);

        return $store->getConfig(
            constant('self::XML_PATH_LOSTBASKET_GUEST_INTERVAL_' . $num)
        );
    }

    /**
     * @param $num
     * @param $storeId
     * @return null|string
     */
    protected function getLostBasketCustomerEnabled($num, $storeId)
    {
        $store   = Mage::app()->getStore($storeId);
        $enabled = $store->getConfig(
            constant('self::XML_PATH_LOSTBASKET_CUSTOMER_ENABLED_' . $num)
        );

        return $enabled;

    }

    /**
     * @param $num
     * @param $storeId
     * @return null|string
     */
    protected function getLostBasketGuestEnabled($num, $storeId)
    {
        $store = Mage::app()->getStore($storeId);

        return $store->getConfig(
            constant('self::XML_PATH_LOSTBASKET_GUEST_ENABLED_' . $num)
        );
    }

    /**
     * @param string $from
     * @param string $to
     * @param bool   $guest
     * @param int    $storeId
     *
     * @return Mage_Eav_Model_Entity_Collection_Abstract
     */
    protected function getStoreQuotes($from = null, $to = null, $guest = false, $storeId = 0)
    {
        $updated = array(
            'from' => $from,
            'to'   => $to,
            'date' => true);

        $salesCollection = Mage::getResourceModel('sales/quote_collection')
            ->addFieldToFilter('is_active', 1)
            ->addFieldToFilter('items_count', array('gt' => 0))
            ->addFieldToFilter('customer_email', array('neq' => ''))
            ->addFieldToFilter('main_table.store_id', $storeId);

        //guests
        if ($guest) {
            $salesCollection->addFieldToFilter(
                'main_table.customer_id', array('null' => true)
            );
        } else {
            //customers
            $salesCollection->addFieldToFilter(
                'main_table.customer_id', array('notnull' => true)
            );
        }

        $salesCollection->addFieldToFilter('main_table.updated_at', $updated);

        if (Mage::helper('ddg/config')->isOnlySubscribersForAC($storeId)) {
            $salesCollection = Mage::getResourceModel('ddg_automation/order')
                ->joinSubscribersOnCollection($salesCollection);
        }

        //process rules on collection
        $ruleModel       = Mage::getModel('ddg_automation/rules');
        $salesCollection = $ruleModel->process(
            $salesCollection, Dotdigitalgroup_Email_Model_Rules::ABANDONED,
            Mage::app()->getStore($storeId)->getWebsiteId()
        );

        return $salesCollection;
    }

    /**
     * Check customer campaign that was sent by a limit from config.
     * Return false for any found for this period.
     *
     * @param $email
     * @param $storeId
     *
     * @return bool
     */
    protected function checkCustomerCartLimit($email, $storeId)
    {

        $cartLimit = Mage::getStoreConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_ABANDONED_CART_LIMIT,
            $storeId
        );

        //no limit is set skip
        if (!$cartLimit) {
            return false;
        }

        //time diff
        $to   = Zend_Date::now($this->locale);
        $from = Zend_Date::now($this->locale)->subHour($cartLimit);

        $updated = array(
            'from' => $from,
            'to'   => $to,
            'date' => true
        );

        //number of campaigns during this time
        $campaignLimit = Mage::getModel('ddg_automation/campaign')
            ->getCollection()
            ->addFieldToFilter('email', $email)
            ->addFieldToFilter('event_name', 'Lost Basket')
            ->addFieldToFilter('sent_at', $updated)
            ->count();

        if ($campaignLimit) {
            return true;
        }

        return false;
    }

    /**
     * AC CUSTOMERS 1.
     *
     * @param $storeId
     * @param $websiteId
     */
    protected function processFirstCustomerAC($storeId, $websiteId)
    {
        //campaign id for customers
        $campaignId = $this->getLostBasketCustomerCampaignId(self::CUSTOMER_LOST_BASKET_ONE, $storeId);

        $from = Zend_Date::now($this->locale)->subMinute(
            $this->getLostBasketCustomerInterval(self::CUSTOMER_LOST_BASKET_ONE, $storeId)
        );
        $to = clone($from);
        $from->sub('5', Zend_Date::MINUTE);

        //active quotes
        $quoteCollection = $this->getStoreQuotes(
            $from->toString('yyyy-MM-dd HH:mm'),
            $to->toString('yyyy-MM-dd HH:mm'),
            $guest = false, $storeId
        );
        //found abandoned carts
        if ( $quoteCollection->getSize()) {
            Mage::helper('ddg')->log(
                'Customer Abandoned Cart 1, from ' . $from->toString('yyyy-MM-dd HH:mm') .
                '  :  ' . $to->toString('yyyy-MM-dd HH:mm') . ', storeId ' . $storeId
            );
        }

        foreach ($quoteCollection as $quote) {
            $quoteId    = $quote->getId();
            $items      = $quote->getAllItems();
            $email      = $quote->getCustomerEmail();

            $itemIds = $this->getQuoteItemIds($items);
            if ($mostExpensiveItem = $this->getMostExpensiveItems($items)) {
                Mage::helper('ddg')->updateAbandonedProductName(
                    $mostExpensiveItem->getName(), $email,
                    $websiteId
                );
            }

            $abandonedModel = Mage::getModel('ddg_automation/abandoned')
                ->loadByQuoteId($quoteId);

            // abandoned cart already sent and the items content are the same
            if ($abandonedModel->getId() && ! $this->isItemsChanged($quote, $abandonedModel)) {
                continue;
            }
            //create abandoned cart
            $this->createAbandonedCart($abandonedModel, $quote, $itemIds);

            //send campaign; check if is valid to be sent
            if ($this->getLostBasketCustomerEnabled(self::CUSTOMER_LOST_BASKET_ONE, $storeId)) {
                $this->sendEmailCampaign($email, $quote, $campaignId, self::CUSTOMER_LOST_BASKET_ONE, $websiteId);
            }
        }
    }

    /**
     * GUESTS 1.
     *
     * @param $storeId
     * @param $websiteId
     */
    protected function proccessFirstGuestAC($storeId, $websiteId)
    {
        $from = Zend_Date::now($this->locale)->subMinute(
            $this->getLostBasketGuestInterval(self::GUEST_LOST_BASKET_ONE, $storeId)
        );
        $to = clone($from);
        $from->sub('5', Zend_Date::MINUTE);

        $quoteCollection = $this->getStoreQuotes(
            $from->toString('yyyy-MM-dd HH:mm'),
            $to->toString('yyyy-MM-dd HH:mm'), $guest = true,
            $storeId
        );

        if ($quoteCollection->getSize()) {
            Mage::helper('ddg')->log(
                'Guest Abandoned Cart 1, from ' . $from->toString('yyyy-MM-dd HH:mm') . '    '
                . $to->toString('yyyy-MM-dd HH:mm') . ', storeId ' . $storeId
            );
        }

        $guestCampaignId = $this->getLostBasketGuestCampaignId(self::GUEST_LOST_BASKET_ONE, $storeId);
        foreach ($quoteCollection as $quote) {
            $quoteId = $quote->getId();
            $items = $quote->getAllItems();
            $email = $quote->getCustomerEmail();

            $itemIds = $this->getQuoteItemIds($items);
            if ($mostExpensiveItem  = $this->getMostExpensiveItems($items)) {
                Mage::helper('ddg')->updateAbandonedProductName(
                    $mostExpensiveItem->getName(), $email,
                    $websiteId
                );
            }

            $abandonedModel = Mage::getModel('ddg_automation/abandoned')
                ->loadByQuoteId($quoteId);

            // abandoned cart already sent and the items content are the same
            if ($abandonedModel->getId() && !$this->isItemsChanged($quote, $abandonedModel)) {
                continue;
            }
            //create abandoned cart
            $this->createAbandonedCart($abandonedModel, $quote, $itemIds);

            //send campaign; check if is enabled and valid to be sent
            if ($this->getLostBasketGuestEnabled(self::GUEST_LOST_BASKET_ONE, $storeId)) {
                $this->sendEmailCampaign($email, $quote, $guestCampaignId, self::GUEST_LOST_BASKET_ONE, $websiteId);
            }
        }
    }

    /**
     * Get most expensive item form the quote. And Product ids.
     *
     * @param $items
     * @return array|bool
     */
    protected function getMostExpensiveItems($items)
    {
        $mostExpensiveItem = false;
        foreach ($items as $item) {
            /** @var $item Mage_Sales_Model_Quote_Item */
            if ($mostExpensiveItem == false) {
                $mostExpensiveItem = $item;
            } elseif ($item->getPrice() > $mostExpensiveItem->getPrice()) {
                $mostExpensiveItem = $item;
            }
        }

        return $mostExpensiveItem;
    }


    /**
     * @param $campaignId
     * @param $storeId
     * @param $websiteId
     * @param $number
     * @param bool $guest
     */
    protected function processExistingAC($campaignId, $storeId, $websiteId, $number, $guest = false)
    {
        if ($guest) {
            $from = Zend_Date::now($this->locale)->subHour($this->getLostBasketGuestInterval($number, $storeId));
            $message = 'Guest';
        } else {
            $from = Zend_Date::now($this->locale)->subHour($this->getLostBasketCustomerInterval($number, $storeId));
            $message = 'Customer';
        }

        $to = clone($from);
        $from->sub('5', Zend_Date::MINUTE);

        //get abandoned carts already sent
        $abandonedCollection = $this->getAbandonedCartsForStore(
            $number,
            $from->toString('yyyy-MM-dd HH:mm'),
            $to->toString('yyyy-MM-dd HH:mm'),
            $storeId,
            $guest
        );

        //quote collection based on the updated date from abandoned cart table
        $quoteIds = $abandonedCollection->getColumnValues('quote_id');
        if (empty($quoteIds)){
            return;
        }
        $quoteCollection = $this->getProcessedQuotesByIds($quoteIds, $storeId);

        //found abandoned carts
        if ($quoteCollection->getSize()) {
            Mage::helper('ddg')->log(
                $message . ' Abandoned Cart ' . $number . ', from ' . $from->toString('yyyy-MM-dd HH:mm') .
                '  :  ' . $to->toString('yyyy-MM-dd HH:mm') . ', storeId ' . $storeId
            );
        }

        /** @var Mage_Sales_Model_Quote $quote */
        foreach ($quoteCollection as $quote) {
            $quoteId = $quote->getId();
            $email = $quote->getCustomerEmail();

            if ($mostExpensiveItem = $this->getMostExpensiveItems($quote->getAllItems())) {
                Mage::helper('ddg')->updateAbandonedProductName(
                    $mostExpensiveItem->getName(), $email,
                    $websiteId
                );
            }

            $isActive = $quote->getIsActive();
            if ($isActive) {
                $this->sendEmailCampaign($email, $quote, $campaignId, $number, $websiteId);
            }

            $abandonedCartUpdateData = [
                'quote_id' => $quoteId,
                'abandoned_cart_number' => $number,
                'is_active' => $isActive,
                'quote_updated_at' => $quote->getUpdatedAt()
            ];

            Mage::getResourceModel('ddg_automation/abandoned')
                ->updateAbandonedCart($abandonedCartUpdateData);
        }
    }

    /**
     * Check if the quote items changed.
     *
     * @param $quote
     * @param $abandonedModel
     * @return bool
     */
    protected function isItemsChanged($quote, $abandonedModel)
    {
        if ($quote->getItemsCount() != $abandonedModel->getItemsCount()) {
            return true;
        } else {
            //number of items matches
            $quoteItemIds = $this->getQuoteItemIds($quote->getAllItems());
            $abandonedItemIds = explode(',', $abandonedModel->getItemsIds());

            //quote items not same
            if (! $this->isItemsIdsSame($quoteItemIds, $abandonedItemIds)) {
                return true;
            }

            return false;
        }
    }

    /**
     * Find the product ids from the quote items.
     *
     * @param $allItemsIds
     * @return array
     */
    protected function getQuoteItemIds($allItemsIds)
    {
        $itemIds = array();
        foreach ($allItemsIds as $item) {
            $itemIds[] = $item->getProductId();
        }

        return $itemIds;
    }

    /**
     * Compare items ids.
     *
     * @param $quoteItemIds
     * @param $abandonedItemIds
     * @return bool
     */
    protected function isItemsIdsSame($quoteItemIds, $abandonedItemIds)
    {
        return $quoteItemIds == $abandonedItemIds;
    }

    /**
     * @param $email
     * @param $quote
     * @param $campaignId
     * @param $number
     * @param $websiteId
     */
    protected function sendEmailCampaign($email, $quote, $campaignId, $number, $websiteId)
    {
        //limit interval if the email was already sent
        $storeId = $quote->getStoreId();
        $campaignFound = $this->checkCustomerCartLimit($email, $storeId);
        //no campaign found for interval pass
        if (! $campaignFound && $campaignId) {
            $customerId = $quote->getCustomerId();
            $message = ($customerId)? 'Abandoned Cart ' . $number : 'Guest Abandoned Cart ' . $number;
            Mage::getModel('ddg_automation/campaign')
                ->setEmail($email)
                ->setCustomerId($customerId)
                ->setEventName(Dotdigitalgroup_Email_Model_Campaign::CAMPAIGN_EVENT_LOST_BASKET)
                ->setQuoteId($quote->getId())
                ->setMessage($message)
                ->setCampaignId($campaignId)
                ->setStoreId($storeId)
                ->setWebsiteId($websiteId)
                ->setSendStatus(Dotdigitalgroup_Email_Model_Campaign::PENDING)
                ->save();
        }
    }

    /**
     * @param $abandonedModel Dotdigitalgroup_Email_Model_Abandoned
     * @param $quote Mage_Sales_Model_Quote
     * @param $itemIds
     */
    protected function createAbandonedCart($abandonedModel, $quote, $itemIds)
    {
        $abandonedModel->setStoreId($quote->getStoreId())
            ->setCustomerId($quote->getCustomerId())
            ->setEmail($quote->getCustomerEmail())
            ->setQuoteId($quote->getId())
            ->setQuoteUpdatedAt($quote->getUpdatedAt())
            ->setAbandonedCartNumber(1)
            ->setItemsCount($quote->getItemsCount())
            ->setItemsIds(implode(',', $itemIds))
            ->save();
    }

    /**
     * @param $number
     * @param $from
     * @param $to
     * @param $storeId
     * @param $guest bool
     * @return mixed
     */
    protected function getAbandonedCartsForStore($number, $from, $to, $storeId, $guest = false)
    {
        $updated = array(
            'from' => $from,
            'to'   => $to,
            'date' => true);

        $abandonedCollection = Mage::getModel('ddg_automation/abandoned')
            ->getCollection()
            ->addFieldToFilter('is_active', 1)
            ->addFieldToFilter('abandoned_cart_number', --$number)
            ->addFieldToFilter('main_table.store_id', $storeId)
            ->addFieldToFilter('quote_updated_at', $updated);

        if ($guest) {
            $abandonedCollection->addFieldToFilter('main_table.customer_id', array('null' => true));
        } else {
            $abandonedCollection->addFieldToFilter('main_table.customer_id', array('notnull' => true));
        }

        if (Mage::helper('ddg/config')->isOnlySubscribersForAC($storeId)) {
            $abandonedCollection = Mage::getResourceModel('ddg_automation/order')
                ->joinSubscribersOnCollection($abandonedCollection, "main_table.email");
        }

        return $abandonedCollection;
    }

    /**
     * Get quotes by ids and process through exclusion rules.
     *
     * @param $quoteIds
     * @param $storeId int
     * @return mixed
     */
    protected function getProcessedQuotesByIds($quoteIds, $storeId)
    {
        $quoteCollection = Mage::getModel('sales/quote')
            ->getCollection()
            ->addFieldToFilter('entity_id', array('in' => $quoteIds));

        //process rules on collection
        $ruleModel       = Mage::getModel('ddg_automation/rules');
        $quoteCollection = $ruleModel->process(
            $quoteCollection,
            Dotdigitalgroup_Email_Model_Rules::ABANDONED,
            Mage::app()->getStore($storeId)->getWebsiteId()
        );

        return $quoteCollection;
    }

}
