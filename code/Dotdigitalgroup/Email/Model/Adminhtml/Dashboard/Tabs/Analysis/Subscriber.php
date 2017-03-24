<?php

class Dotdigitalgroup_Email_Model_Adminhtml_Dashboard_Tabs_Analysis_Subscriber
    extends Mage_Core_Model_Abstract
{

    /**
     * @var
     */
    public $storeIds;

    /**
     * @return mixed
     */
    protected function calculateOperationalDaysFromOrder()
    {
        $collection = Mage::getResourceModel('sales/order_collection');
        $collection->addFieldToSelect('created_at');

        if (is_array($this->storeIds) && ! empty($this->storeIds)) {
            $collection->addFieldToFilter(
                'store_id', array('in' => $this->storeIds)
            );
        }

        //@codingStandardsIgnoreStart
        $collection->getSelect()->columns(
            array(
                'days' => "DATEDIFF(date(NOW()) , date(MIN(created_at)))"
            )
        );

        return $collection->setPageSize(1)->setCurPage(1)->getFirstItem()
            ->getDays();
        //@codingStandardsIgnoreEnd
    }

    /**
     * @return Mage_Newsletter_Model_Resource_Subscriber_Collection|Object
     */
    protected function _getCollection()
    {
        $collection = Mage::getResourceModel(
            'newsletter/subscriber_collection'
        );
        $collection->addFieldToFilter('subscriber_status', array('neq' => '3'));

        if (is_array($this->storeIds) && ! empty($this->storeIds)) {
            $collection->addFieldToFilter(
                'store_id', array('in' => $this->storeIds)
            );
        }

        return $collection;
    }

    /**
     * Prepare collection.
     *
     * @return Varien_Object
     */
    protected function getPreparedCollection()
    {
        //all active subscribers
        $collection       = $this->_getCollection();
        $totalSubscribers = $collection->getSize();

        //all active subscribers who are also customers
        $customerSubscribers     = $collection->addFieldToFilter(
            'customer_id', array('neq' => '0')
        );
        $customerSubscriberCount = $customerSubscribers->getSize();

        $days = $this->calculateOperationalDaysFromOrder();
        if ($days) {
            $subscribersPerDay = number_format($totalSubscribers / $days, 2);
        } else {
            $subscribersPerDay = $totalSubscribers;
        }

        $resultObject = new Varien_Object;
        $resultObject
            ->setTotalSubscriber($totalSubscribers)
            ->setTotalSubscriberCustomer($customerSubscriberCount)
            ->setSubscribersPerDay($subscribersPerDay);

        return $resultObject;
    }

    /**
     * @param int $store
     * @param int $website
     * @param int $group
     *
     * @return Varien_Object
     * @throws Mage_Core_Exception
     */
    public function getLifetimeSubscribers($store = 0, $website = 0, $group = 0)
    {
        if ($store) {
            $this->storeIds = array($store => $store);
        } else if ($website) {
            $storeIds       = Mage::app()->getWebsite($website)->getStoreIds();
            $this->storeIds = $storeIds;
        } else if ($group) {
            $storeIds       = Mage::app()->getGroup($group)->getStoreIds();
            $this->storeIds = $storeIds;
        }

        return $this->getPreparedCollection();
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return "Subscribers Analytical Data";
    }
}