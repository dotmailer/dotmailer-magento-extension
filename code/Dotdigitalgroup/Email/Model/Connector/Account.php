<?php

class Dotdigitalgroup_Email_Model_Connector_Account
{

    /**
     * @var string
     */
    public $apiUsername;
    /**
     * @var string
     */
    public $apiPassword;
    /**
     * @var int
     */
    public $limit;
    /**
     * @var array
     */
    public $contactBookIds;
    /**
     * @var array
     */
    public $subscriberBookIds;
    /**
     * @var array
     */
    public $websites = array();
    /**
     * @var array
     */
    public $csvHeaders;
    /**
     * @var string
     */
    public $customersFilename;
    /**
     * @var string
     */
    public $subscribersFilename;
    /**
     * @var array
     */
    public $mappingHash;
    /**
     * @var array
     */
    public $contacts = array();
    /**
     * @var array
     */
    public $orders = array();
    /**
     * @var array
     */
    public $orderIds = array();
    /**
     * @var array
     */
    public $ordersForSingleSync = array();
    /**
     * @var array
     */
    public $orderIdsForSingleSync = array();

    /**
     * @param $apiPassword
     *
     * @return $this
     */
    public function setApiPassword($apiPassword)
    {
        $this->apiPassword = $apiPassword;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getApiPassword()
    {
        return $this->apiPassword;
    }

    /**
     * @param $apiUsername
     *
     * @return $this
     */
    public function setApiUsername($apiUsername)
    {
        $this->apiUsername = $apiUsername;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getApiUsername()
    {
        return $this->apiUsername;
    }

    /**
     * @param string $contactBookIds
     */
    public function setContactBookId($contactBookIds)
    {
        $this->contactBookIds[$contactBookIds] = $contactBookIds;
    }

    /**
     * @return array
     */
    public function getContactBookIds()
    {
        return $this->contactBookIds;
    }

    /**
     * @param array $contacts
     */
    public function setContacts($contacts)
    {
        if (!empty($this->contacts)) {
            $this->contacts += $contacts;
        } else {
            $this->contacts[] = $contacts;
        }
    }

    /**
     * @return array
     */
    public function getContacts()
    {
        return $this->contacts;
    }

    /**
     * @param mixed $customersFilename
     */
    public function setCustomersFilename($customersFilename)
    {
        $this->customersFilename = $customersFilename;
    }

    /**
     * @return mixed
     */
    public function getCustomersFilename()
    {
        return $this->customersFilename;
    }

    /**
     * @param mixed $limit
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;
    }

    /**
     * @return mixed
     */
    public function getLimit()
    {
        return $this->limit;
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
     * @param array $orders
     */
    public function setOrders($orders)
    {
        foreach ($orders as $order) {
            $this->orders[$order->id] = $order;
        }
    }

    /**
     * @return array
     */
    public function getOrders()
    {
        return $this->orders;
    }

    /**
     * @param string $subscriberBookIds
     */
    public function setSubscriberBookId($subscriberBookIds)
    {
        $this->subscriberBookIds[$subscriberBookIds] = $subscriberBookIds;
    }

    /**
     * @return array
     */
    public function getSubscriberBookIds()
    {
        return $this->subscriberBookIds;
    }

    /**
     * @param mixed $subscribersFilename
     */
    public function setSubscribersFilename($subscribersFilename)
    {
        $this->subscribersFilename = $subscribersFilename;
    }

    /**
     * @return mixed
     */
    public function getSubscribersFilename()
    {
        return $this->subscribersFilename;
    }

    /**
     * @param mixed $csvHeaders
     */
    public function setCsvHeaders($csvHeaders)
    {
        $this->csvHeaders = $csvHeaders;
    }

    /**
     * @return mixed
     */
    public function getCsvHeaders()
    {
        return $this->csvHeaders;
    }

    /**
     * @param mixed $websites
     */
    public function setWebsites($websites)
    {
        $this->websites[] = $websites;
    }

    /**
     * @return mixed
     */
    public function getWebsites()
    {
        return $this->websites;
    }

    /**
     * @param array $orderIds
     */
    public function setOrderIds($orderIds)
    {
        $this->orderIds = $orderIds;
    }

    /**
     * @return array
     */
    public function getOrderIds()
    {
        return $this->orderIds;
    }

    /**
     * @param array $orders
     */
    public function setOrdersForSingleSync($orders)
    {
        foreach ($orders as $order) {
            $this->ordersForSingleSync[$order->id] = $order;
        }
    }

    /**
     * @return array
     */
    public function getOrdersForSingleSync()
    {
        return $this->ordersForSingleSync;
    }

    /**
     * @param array $orderIds
     */
    public function setOrderIdsForSingleSync($orderIds)
    {
        $this->orderIdsForSingleSync = $orderIds;
    }

    /**
     * @return array
     */
    public function getOrderIdsForSingleSync()
    {
        return $this->orderIdsForSingleSync;
    }
}