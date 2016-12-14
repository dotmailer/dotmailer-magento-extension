<?php

class Dotdigitalgroup_Email_Model_Apiconnector_Subscriber
{

    /**
     * Subscriber
     *
     * @var Mage_Newsletter_Model_Subscriber $subscriber
     */
    public $subscriber;
    public $subscriberData;
    protected $_mappingHash;

    /**
     * constructor, mapping hash to map.
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
        $this->subscriberData[] = $data;
    }

    /**
     * Set subscriber data with sales.
     *
     * @param Mage_Newsletter_Model_Subscriber $subscriber
     */
    public function setSubscriberData(Mage_Newsletter_Model_Subscriber $subscriber)
    {
        $this->subscriber = $subscriber;
        foreach ($this->getMappingHash() as $key => $field) {

            //Call user function based on the attribute mapped.
            $function = 'get';
            $exploded = explode('_', $key);
            foreach ($exploded as $one) {
                $function .= ucfirst($one);
            }
            try {
                $value = call_user_func(
                    array('self', $function)
                );
                $this->subscriberData[$key] = $value;
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
    }

    /**
     * @param mixed $mappingHash
     */
    public function setMappingHash($mappingHash)
    {
        $this->_mappingHash = $mappingHash;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getMappingHash()
    {
        return $this->_mappingHash;
    }

    /**
     * export to CSV.
     *
     * @return mixed
     */
    public function toCSVArray()
    {
        $result = $this->subscriberData;

        return $result;
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
     * get numbser of orders.
     *
     * @return mixed
     */
    public function getNumberOfOrders()
    {
        if ($this->subscriber->getNumberOfOrders()) {
            return $this->subscriber->getNumberOfOrders();
        }
        return '';
    }

    /**
     * get average order value.
     *
     * @return mixed
     */
    public function getAverageOrderValue()
    {
        if ($this->subscriber->getAverageOrderValue()) {
            return $this->subscriber->getAverageOrderValue();
        }
        return '';
    }

    /**
     * get total spend.
     *
     * @return mixed
     */
    public function getTotalSpend()
    {
        if ($this->subscriber->getTotalSpend()) {
            return $this->subscriber->getTotalSpend();
        }
        return '';
    }

    /**
     * get last order date.
     *
     * @return mixed
     */
    public function getLastOrderDate()
    {
        if ($this->subscriber->getLastOrderDate()) {
            return $this->subscriber->getLastOrderDate();
        }
        return '';
    }

    /**
     * get last order id.
     *
     * @return mixed
     */
    public function getLastOrderId()
    {
        if ($this->subscriber->getLastOrderId()) {
            return $this->subscriber->getLastOrderId();
        }
        return '';
    }

    /**
     * get last increment id
     *
     * @return mixed
     */
    public function getLastIncrementId()
    {
        if ($this->subscriber->getLastIncrementId()) {
            return $this->subscriber->getLastIncrementId();
        }
        return '';
    }

    protected function _getWebsiteName()
    {
        $storeId = $this->subscriber->getStoreId();
        $website = Mage::app()->getStore($storeId)->getWebsite();
        if ($website) {
            return $website->getName();
        }

        return '';
    }

    protected function _getStoreName()
    {
        $storeId = $this->subscriber->getStoreId();
        $store = Mage::app()->getStore($storeId);
        if ($store) {
            return $store->getName();
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
        $id = $this->subscriber->getMostCategoryId();
        if ($id) {
            return Mage::getModel('catalog/category')
                ->load($id)
                ->setStoreId($this->subscriber->getStoreId())
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
        $brand = $this->subscriber->getMostBrand();
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
        $day = $this->subscriber->getWeekDay();
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
        $month = $this->subscriber->getMonthDay();
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
        $id = $this->subscriber->getFirstCategoryId();
        if ($id) {
            return Mage::getModel('catalog/category')
                ->load($id)
                ->setStoreId($this->subscriber->getStoreId())
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
        $id = $this->subscriber->getLastCategoryId();
        if ($id) {
            return Mage::getModel('catalog/category')
                ->setStoreId($this->subscriber->getStoreId())
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
        $id = $this->subscriber->getProductIdForFirstBrand();

        return $this->_getBrandValue($id);
    }

    /**
     * get last purchased brand
     *
     * @return string
     */
    public function getLastBrandPur()
    {
        $id = $this->subscriber->getProductIdForLastBrand();

        return $this->_getBrandValue($id);
    }

    protected function _getBrandValue($id)
    {
        $attribute = Mage::helper('ddg')->getWebsiteConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_DATA_FIELDS_BRAND_ATTRIBUTE,
            Mage::app()->getStore($this->subscriber->getStoreId())->getWebsiteId()
        );
        if ($id && $attribute) {
            $brand = Mage::getModel('catalog/product')
                ->setStoreId($this->subscriber->getStoreId())
                ->load($id)
                ->getAttributeText($attribute);
            if ($brand) {
                return $brand;
            }
        }

        return "";
    }
}