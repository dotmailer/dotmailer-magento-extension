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


    /**
     * Number of lost baskets available.
     *
     * @var array
     */
    public $lostBasketCustomers = array(1, 2, 3);
    /**
     * Number of guest lost baskets available.
     *
     * @var array
     */
    public $lostBasketGuests = array(1, 2, 3);

    /**
     * @var
     */
    private $locale;

    /**
     *
     */
    public function proccessAbandonedCarts()
    {
        $this->locale = Mage::app()->getLocale()->getLocale();
        foreach (Mage::app()->getStores() as $store) {
            $storeId = $store->getStoreId();
            $websiteId = $store->getWebsiteId();
            //PROCCESS FIRST ABANDONED CART
            $this->proccessFirtstCustomerAC($storeId, $websiteId);
            $this->proccessFirtstGuestAC($storeId, $websiteId);


            //PROCCESS 2'ND AND 3'RD
            //$this->proccessExistingCustomerAC($storeId);
            //$this->processExistingGuestAC($storeId);

        }
    }

    /**
     * @param $num
     * @param $storeId
     * @return null|string
     */
    protected function _getLostBasketCustomerCampaignId($num, $storeId)
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
    protected function _getLostBasketGuestCampaignId($num, $storeId)
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
    protected function _getLostBasketCustomerInterval($num, $storeId)
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
    protected function _getLostBasketGuestIterval($num, $storeId)
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
    protected function _getLostBasketCustomerEnabled($num, $storeId)
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
    protected function _getLostBasketGuestEnabled($num, $storeId)
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
    protected function _getStoreQuotes($from = null, $to = null, $guest = false, $storeId = 0)
    {
        $updated = array(
            'from' => $from,
            'to'   => $to,
            'date' => true);

        $salesCollection = Mage::getResourceModel('sales/quote_collection')
            ->addFieldToFilter('is_active', 1)
            ->addFieldToFilter('items_count', array('gt' => 0))
            ->addFieldToFilter('customer_email', array('neq' => ''))
            ->addFieldToFilter('store_id', $storeId);

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
    protected function _checkCustomerCartLimit($email, $storeId)
    {

        $cartLimit = Mage::getStoreConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_ABANDONED_CART_LIMIT,
            $storeId
        );
        $locale    = Mage::app()->getLocale()->getLocale();

        //no limit is set skip
        if (!$cartLimit) {
            return false;
        }

        //time diff
        $to   = Zend_Date::now($locale);
        $from = Zend_Date::now($locale)->subHour($cartLimit);

        $updated = array(
            'from' => $from,
            'to'   => $to,
            'date' => true
        );

        //number of campigns during this time
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
     * @param $storeId
     * @param $websiteId
     */
    private function proccessFirtstCustomerAC($storeId, $websiteId)
    {
        //customer enabled
        if ($this->_getLostBasketCustomerEnabled(1, $storeId)) {

            $from = Zend_Date::now($this->locale)->subMinute($this->_getLostBasketCustomerInterval(1, $storeId));
            $to = clone($from);
            //@cdiacon todo get the last run time from the cronjob table for abanadoned carts
            $from->sub('5', Zend_Date::MINUTE);

            //active quotes
            $quoteCollection = $this->_getStoreQuotes(
                $from->toString('yyyy-MM-dd HH:mm'),
                $to->toString('yyyy-MM-dd HH:mm'),
                $guest = false, $storeId
            );
            //found abandoned carts
            if (!$quoteCollection->getSize()) {
                Mage::helper('ddg')->log(
                    'Customer AC 1, from : '
                    . $from->toString('yyyy-MM-dd HH:mm') .
                    '  :  ' . $to->toString('yyyy-MM-dd HH:mm')
                );
            }

            //campaign id for customers
            $campaignId = $this->_getLostBasketCustomerCampaignId(1, $storeId);

            foreach ($quoteCollection as $quote) {
                $itemIds = array();
                $mostExpensiveItem = false;
                $quoteId    = $quote->getId();
                $items      = $quote->getAllItems();
                $email      = $quote->getCustomerEmail();
                // update last quote id for the contact
                Mage::helper('ddg')->updateLastQuoteId($quoteId, $email, $websiteId);

                foreach ($items as $item) {
                    /** @var $item Mage_Sales_Model_Quote_Item */
                    if ($mostExpensiveItem == false) {
                        $mostExpensiveItem = $item;
                    } elseif ($item->getPrice() > $mostExpensiveItem->getPrice()) {
                        $mostExpensiveItem = $item;
                    }
                    $itemIds[] = $item->getProductId();
                }

                if ($mostExpensiveItem) {
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

                //send campaign
                $this->sendEmailCampaign($email, $quote, $campaignId, $websiteId);
            }
        }
    }

    private function proccessFirtstGuestAC($storeId, $websiteId)
    {
        if ($this->_getLostBasketGuestEnabled(1, $storeId)) {

            $from = Zend_Date::now($this->locale)->subMinute($this->_getLostBasketGuestIterval(1, $storeId));
            $to = clone($from);
            //@todo get the last cron run from the cron_schedule table
            $from->sub('5', Zend_Date::MINUTE);
            $quoteCollection = $this->_getStoreQuotes(
                $from->toString('yyyy-MM-dd HH:mm'),
                $to->toString('yyyy-MM-dd HH:mm'), $guest = true,
                $storeId
            );

            if ($quoteCollection->getSize()) {
                Mage::helper('ddg')->log(
                    'Guest AC 1 from : '
                    . $from->toString('yyyy-MM-dd HH:mm') . ':'
                    . $to->toString('yyyy-MM-dd HH:mm')
                );
            }

            $guestCampaignId = $this->_getLostBasketGuestCampaignId(1, $storeId);
            foreach ($quoteCollection as $quote) {
                $itemIds = array();
                $mostExpensiveItem = false;
                $quoteId = $quote->getId();
                $items = $quote->getAllItems();
                $email = $quote->getCustomerEmail();
                // update last quote id for the contact
                Mage::helper('ddg')->updateLastQuoteId($quoteId, $email, $websiteId);

                foreach ($items as $item) {
                    /** @var $item Mage_Sales_Model_Quote_Item */
                    if ($mostExpensiveItem == false) {
                        $mostExpensiveItem = $item;
                    } elseif ($item->getPrice() > $mostExpensiveItem->getPrice()) {
                        $mostExpensiveItem = $item;
                    }
                    $itemIds[] = $item->getProductId();
                }

                if ($mostExpensiveItem) {
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

                //send campaign
                $this->sendEmailCampaign($email, $quote, $guestCampaignId, $websiteId);
            }
        }
    }

    private function proccessExistingCustomerAC()
    {
    }

    private function processExistingGuestAC()
    {
    }

    /**
     * Check if the quote items changed.
     *
     * @param $quote
     * @param $abandonedModel
     * @return bool
     */
    private function isItemsChanged($quote, $abandonedModel)
    {
        //same item number - check for product ids
        if ($quote->getItemsCount() == $abandonedModel->getItemsCount()) {
            $quoteItemIds = $this->getQuoteItemIds($quote->getAllItems());
            $abandonedItemIds = explode(',', $abandonedModel->getItemsIds());

            //quote items are the same
            if (! $this->isItemsIdsSame($quoteItemIds, $abandonedItemIds)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Find the product ids from the quote items.
     *
     * @param $allItemsIds
     * @return array
     */
    private function getQuoteItemIds($allItemsIds)
    {
        $itemIds = array();
        foreach ($allItemsIds as $item) {
            $itemIds[] = $item->getProductId();
        }

        return $itemIds;
    }

    /**
     * Compare the array ids.
     *
     * @param $quoteItemIds
     * @param $abandonedItemIds
     * @return bool
     */
    private function isItemsIdsSame($quoteItemIds, $abandonedItemIds)
    {
        return $quoteItemIds == $abandonedItemIds;
    }

    /**
     * @param $email
     * @param $quote
     * @param $campaignId
     * @param $websiteId
     */
    private function sendEmailCampaign($email, $quote, $campaignId, $websiteId)
    {
        //limit interval if the email was already sent
        $storeId = $quote->getStoreId();
        $campignFound = $this->_checkCustomerCartLimit($email, $storeId);
        //no campign found for interval pass
        if (! $campignFound) {
            Mage::getModel('ddg_automation/campaign')
                ->setEmail($email)
                ->setCustomerId($quote->getCustomerId())
                ->setEventName('Lost Basket')
                ->setQuoteId($quote->getId())
                ->setMessage('Abandoned Cart : 1')
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
    private function createAbandonedCart($abandonedModel, $quote, $itemIds)
    {
        $abandonedModel->setStoreId($quote->getStoreId())
            ->setCustomerId($quote->getCustomerId())
            ->setQuoteId($quote->getId())
            ->setQuoteUpdatedAt($quote->getUpdatedAt())
            ->setAbandonedCartNumber(1)
            ->setItemsCount($quote->getItemsCount())
            ->setItemsIds(implode(',', $itemIds))
            ->save();
    }
}