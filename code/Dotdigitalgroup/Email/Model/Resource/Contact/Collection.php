<?php

class Dotdigitalgroup_Email_Model_Resource_Contact_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{

    /**
     * Constructor.
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('ddg_automation/contact');
    }

    /**
     * @param $website
     *
     * @return $this
     */
    public function addWebsiteFilter($website)
    {
        $this->addFilter('website_id', $website);

        return $this;
    }

    /**
     * @param $email
     * @param $websiteId
     * @return bool|Varien_Object
     */
    public function loadByCustomerEmail($email, $websiteId)
    {
        $collection = $this->addFieldToFilter('email', $email)
            ->addFieldToFilter('website_id', $websiteId)
            ->setPageSize(1);

        if ($collection->getSize()) {
            return $collection->getFirstItem();
        }

        return false;
    }

}