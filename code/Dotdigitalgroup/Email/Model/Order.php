<?php

class Dotdigitalgroup_Email_Model_Order extends Mage_Core_Model_Abstract
{
    const EMAIL_ORDER_NOT_IMPORTED = null;
    /**
     * constructor
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('ddg_automation/order');
    }

    /**
     * @return $this|Mage_Core_Model_Abstract
     */
    protected function _beforeSave()
    {
        parent::_beforeSave();
        $now = Mage::getSingleton('core/date')->gmtDate();
        if ($this->isObjectNew()) {
            $this->setCreatedAt($now);
        }
        return $this;
    }


    /**
     * Load the email order by quote id.
     * @param $orderId
     * @param $quoteId
     * @return $this|Varien_Object
     */
    public function loadByOrderId($orderId, $quoteId)
    {
        $collection = $this->getCollection()
            ->addFieldToFilter('order_id', $orderId)
            ->addFieldToFilter('quote_id', $quoteId)
            ->setPageSize(1);

        if ($collection->count()) {
            return $collection->getFirstItem();
        } else {
            $this->setOrderId($orderId)
                ->setQuoteId($quoteId);
        }
        return $this;
    }


	/**
	 * @param $orderId
	 * @param $quoteId
	 * @param $storeId
	 *
	 * @return $this|Varien_Object
	 */
	public function getEmailOrderRow($orderId, $quoteId, $storeId)
    {
        $collection = $this->getCollection()
            ->addFieldToFilter('order_id', $orderId)
            ->addFieldToFilter('quote_id', $quoteId)
            ->addFieldToFilter('store_id', $storeId);

        if ($collection->count()) {
            return $collection->getFirstItem();
        } else {
            $now = Mage::getSingleton('core/date')->gmtDate();

            $this->setOrderId($orderId)
                ->setQuoteId($quoteId)
                ->setStoreId($storeId)
                ->setCreatedAt($now);
        }
        return $this;

    }

	/**
	 * Get all orders with particular status within certain days.
	 *
	 * @param $storeIds
	 * @param $limit
	 * @param $orderStatuses
     * @param $modified
	 *
	 * @return Dotdigitalgroup_Email_Model_Resource_Order_Collection
	 */
    public function getOrdersToImport($storeIds, $limit, $orderStatuses, $modified = false)
    {
        $collection = $this->getCollection()
            ->addFieldToFilter('store_id', array('in' => $storeIds))
            ->addFieldToFilter('order_status', array('in' => $orderStatuses));

        if ($modified) {
            $collection
                ->addFieldToFilter('email_imported', 1)
                ->addFieldToFilter('modified', 1);
        } else
            $collection->addFieldToFilter('email_imported', array('null' => true));


        $collection->getSelect()->limit($limit);
        return $collection;
    }

    /**
     * Get all sent orders older then certain days.
     *
     * @param $storeIds
     * @param $limit
     *
     * @return Dotdigitalgroup_Email_Model_Resource_Order_Collection
     */
    public function getAllSentOrders($storeIds, $limit)
    {
        $collection = $this->getCollection()
            ->addFieldToFilter('email_imported', 1)
            ->addFieldToFilter('store_id', array('in' => $storeIds));

        $collection->getSelect()->limit($limit);

	    return $collection;
    }

}