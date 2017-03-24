<?php

class Dotdigitalgroup_Email_Model_Contact extends Mage_Core_Model_Abstract
{

    const EMAIL_CONTACT_IMPORTED = 1;
    const EMAIL_CONTACT_NOT_IMPORTED = null;
    const EMAIL_SUBSCRIBER_NOT_IMPORTED = null;
    const EMAIL_SUBSCRIBER_IMPORTED = 1;

    /**
     * Constructor.
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('ddg_automation/contact');
    }

    /**
     * Load contact by customer id.
     *
     * @param $customerId
     *
     * @return mixed
     */
    public function loadByCustomerId($customerId)
    {
        $collection = $this->getCollection()
            ->addFieldToFilter('customer_id', $customerId)
            ->setPageSize(1);

        if ($collection->getSize()) {
            //@codingStandardsIgnoreStart
            return $collection->getFirstItem();
            //@codingStandardsIgnoreEnd
        }

        return $this;
    }

    /**
     * Get all customer contacts not imported for a website.
     *
     * @param     $websiteId
     * @param int $pageSize
     *
     * @return Dotdigitalgroup_Email_Model_Resource_Contact_Collection
     */
    public function getContactsToImportForWebsite($websiteId, $pageSize = 100)
    {
        $collection = $this->getCollection()
            ->addFieldToFilter('website_id', $websiteId)
            ->addFieldToFilter('email_imported', array('null' => true))
            ->addFieldToFilter('customer_id', array('neq' => '0'));


        //@codingStandardsIgnoreStart
        $collection->getSelect()->limit($pageSize);
        //@codingStandardsIgnoreEnd

        return $collection;
    }

    /**
     * Get missing contacts.
     *
     * @param     $websiteId
     * @param int $pageSize
     *
     * @return mixed
     */
    public function getMissingContacts($websiteId, $pageSize = 100)
    {
        $collection = $this->getCollection()
            ->addFieldToFilter('contact_id', array('null' => true))
            ->addFieldToFilter('suppressed', array('null' => true))
            ->addFieldToFilter('website_id', $websiteId);

        //@codingStandardsIgnoreStart
        $collection->getSelect()->limit($pageSize);
        //@codingStandardsIgnoreEnd

        return $collection;
    }

    /**
     * Load Contact by Email.
     *
     * @param $email
     * @param $websiteId
     *
     * @return $this
     */
    public function loadByCustomerEmail($email, $websiteId)
    {
        $collection = $this->getCollection()
            ->addFieldToFilter('email', $email)
            ->addFieldToFilter('website_id', $websiteId)
            ->setPageSize(1);

        if ($collection->getSize()) {
            //@codingStandardsIgnoreStart
            return $collection->getFirstItem();
            //@codingStandardsIgnoreEnd
        } else {
            $this->setEmail($email)
                ->setWebsiteId($websiteId);
        }

        return $this;
    }

    /**
     *
     * @param $website
     * @param int $limit
     * @return Mage_Eav_Model_Entity_Collection_Abstract
     */
    public function getSubscribersToImport($website, $limit = 1000)
    {
        $storeIds = $website->getStoreIds();
        $collection = $this->getCollection()
            ->addFieldToFilter('is_subscriber', array('notnull' => true))
            ->addFieldToFilter('subscriber_imported', array('null' => true))
            ->addFieldToFilter('store_id', array('in' => $storeIds));

        //@codingStandardsIgnoreStart
        $collection->getSelect()->limit($limit);
        //@codingStandardsIgnoreEnd
        return $collection;
    }


    /**
     * @param $website
     * @param int $limit
     * @return Mage_Eav_Model_Entity_Collection_Abstract
     */
    public function getSubscribersWithCustomerIdToImport($website, $limit = 1000)
    {
        $storeIds = $website->getStoreIds();
        $collection = $this->getCollection()
            ->addFieldToFilter('is_subscriber', array('notnull' => true))
            ->addFieldToFilter('subscriber_imported', array('null' => true))
            ->addFieldToFilter('store_id', array('in' => $storeIds))
            ->addFieldToFilter('customer_id', array('neq' => 0));

        //@codingStandardsIgnoreStart
        $collection->getSelect()->limit($limit);
        //@codingStandardsIgnoreEnd
        return $collection;
    }

    /**
     * @param $website
     * @param int $limit
     * @return Mage_Eav_Model_Entity_Collection_Abstract
     */
    public function getGuestSubscribersToImport($website, $limit = 1000)
    {
        $storeIds = $website->getStoreIds();
        $collection = $this->getCollection()
            ->addFieldToFilter('is_subscriber', array('notnull' => true))
            ->addFieldToFilter('subscriber_imported', array('null' => true))
            ->addFieldToFilter('store_id', array('in' => $storeIds))
            ->addFieldToFilter('customer_id', array('eq' => 0));

        //@codingStandardsIgnoreStart
        $collection->getSelect()->limit($limit);
        //@codingStandardsIgnoreEnd
        return $collection;
    }

    /**
     * @param $emails
     * @return Mage_Eav_Model_Entity_Collection_Abstract
     */
    public function getContactWithEmails($emails)
    {
        $collection = $this->getCollection()
            ->addFieldToFilter('email', array('in' => $emails));

        return $collection;
    }

    /**
     * Get all not imported guests for a website.
     *
     * @param $website
     *
     * @return Dotdigitalgroup_Email_Model_Resource_Contact_Collection
     */
    public function getGuests($website)
    {
        $guestCollection = $this->getCollection()
            ->addFieldToFilter('is_guest', array('notnull' => true))
            ->addFieldToFilter('email_imported', array('null' => true))
            ->addFieldToFilter('website_id', $website->getId());

        return $guestCollection;
    }

    /**
     * @return int
     */
    public function getNumberOfImportedContacs()
    {
        $collection = $this->getCollection()
            ->addFieldToFilter('email_imported', array('notnull' => true));

        return $collection->getSize();
    }

    /**
     * Get the number of customers for a website.
     *
     * @param int $websiteId
     *
     * @return int
     */
    public function getNumberCustomerContacts($websiteId = 0)
    {
        $countContacts = Mage::getModel('ddg_automation/contact')
            ->getCollection()
            ->addFieldToFilter('customer_id', array('gt' => '0'))
            ->addFieldToFilter('website_id', $websiteId)
            ->getSize();

        return $countContacts;
    }

    /**
     *
     * Get number of suppressed contacts as customer.
     *
     * @param int $websiteId
     *
     * @return int
     */
    public function getNumberCustomerSuppressed($websiteId = 0)
    {
        $countContacts = Mage::getModel('ddg_automation/contact')
            ->getCollection()
            ->addFieldToFilter('customer_id', array('gt' => 0))
            ->addFieldToFilter('website_id', $websiteId)
            ->addFieldToFilter('suppressed', '1')
            ->getSize();

        return $countContacts;
    }

    /**
     * Get number of synced customers.
     *
     * @param int $websiteId
     *
     * @return int
     */
    public function getNumberCustomerSynced($websiteId = 0)
    {
        $countContacts = Mage::getModel('ddg_automation/contact')
            ->getCollection()
            ->addFieldToFilter('customer_id', array('gt' => 0))
            ->addFieldToFilter('website_id', $websiteId)
            ->addFieldToFilter('email_imported', '1')
            ->getSize();

        return $countContacts;

    }

    /**
     * Get number of subscribers synced.
     *
     * @param int $websiteId
     *
     * @return int
     */
    public function getNumberSubscribersSynced($websiteId = 0)
    {
        $countContacts = Mage::getModel('ddg_automation/contact')
            ->getCollection()
            ->addFieldToFilter(
                'subscriber_status',
                Dotdigitalgroup_Email_Model_Newsletter_Subscriber::STATUS_SUBSCRIBED
            )
            ->addFieldToFilter('subscriber_imported', '1')
            ->addFieldToFilter('website_id', $websiteId)
            ->getSize();

        return $countContacts;
    }

    /**
     * Get number of subscribers.
     *
     * @param int $websiteId
     *
     * @return int
     */
    public function getNumberSubscribers($websiteId = 0)
    {

        $countContacts = Mage::getModel('ddg_automation/contact')
            ->getCollection()
            ->addFieldToFilter(
                'subscriber_status',
                Dotdigitalgroup_Email_Model_Newsletter_Subscriber::STATUS_SUBSCRIBED
            )
            ->addFieldToFilter('website_id', $websiteId)
            ->getSize();

        return $countContacts;
    }


    /**
     * Get emails with order data.
     *
     * @param $emails
     * @return array
     */
    public function getSalesOrderWithCutomerEmails($emails)
    {
        if (empty($emails))
            return array();

        $collection = Mage::getResourceModel('sales/order_collection')
            ->addFieldToFilter('customer_email', array('in' => $emails));

        return $collection->getColumnValues('customer_email');
    }
}