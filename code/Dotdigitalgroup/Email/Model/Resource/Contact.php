<?php

class Dotdigitalgroup_Email_Model_Resource_Contact extends Mage_Core_Model_Resource_Db_Abstract
{
    /**
     * Constructor.
     */
    protected function _construct()
    {
        $this->_init('ddg_automation/contact', 'email_contact_id');
    }

    /**
     * Remove contact id's.
     *
     * @return int
     */
    public function deleteContactIds()
    {
        try {
            $conn = $this->getReadConnection();
            $num  = $conn->update(
                $this->getMainTable(),
                array('contact_id' => new Zend_Db_Expr('null')),
                $conn->quoteInto(
                    'contact_id is ?', new Zend_Db_Expr('not null')
                )
            );

            return $num;
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * Reset the imported contacts.
     *
     * @return int
     */
    public function resetAllContacts()
    {
        try {
            $conn = $this->_getWriteAdapter();
            $num  = $conn->update(
                $this->getMainTable(),
                array('email_imported' => new Zend_Db_Expr('null')),
                $conn->quoteInto(
                    'email_imported is ?', new Zend_Db_Expr('not null')
                )
            );

            return $num;
        } catch (Exception $e) {
            Mage::logException($e);
            return 0;
        }
    }

    /**
     * Set all imported subscribers for re-import.
     *
     * @return int
     */
    public function resetSubscribers()
    {
        try {
            $conn = $this->_getWriteAdapter();
            $num  = $conn->update(
                $this->getMainTable(),
                array('subscriber_imported' => new Zend_Db_Expr('null')),
                $conn->quoteInto(
                    'subscriber_imported is ?', new Zend_Db_Expr('not null')
                )
            );

            return $num;
        } catch (Exception $e) {
            Mage::logException($e);
            return 0;
        }
    }

    /**
     * Reset the imported contacts as guest.
     *
     * @return int
     */
    public function resetAllGuestContacts()
    {
        try {
            $conn    = $this->_getWriteAdapter();
            $where   = array();
            $where[] = $conn->quoteInto(
                'email_imported is ?', new Zend_Db_Expr('not null')
            );
            $where[] = $conn->quoteInto(
                'is_guest is ?', new Zend_Db_Expr('not null')
            );

            $num = $conn->update(
                $this->getMainTable(),
                array('email_imported' => new Zend_Db_Expr('null')),
                $where
            );

            return $num;
        } catch (Exception $e) {
            Mage::logException($e);
            return 0;
        }
    }

    /**
     * Re-set all tables.
     */
    public function resetAllTables()
    {
        $conn = $this->_getWriteAdapter();
        try {
            //remove Engagement Cloud code from core_resource table
            $cond = $conn->quoteInto('code = ?', 'email_connector_setup');
            $conn->delete(
                Mage::getSingleton('core/resource')->getTableName('core_resource'), $cond
            );

            //clean cache
            Mage::app()->getCacheInstance()->flush();
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * @param $customer
     */
    public function update($customer)
    {
        $write = $this->_getWriteAdapter();
        if (is_array($customer)) {
            $ids = implode(', ', $customer);
            $write->update(
                $this->getMainTable(), array('email_imported' => 1),
                "customer_id IN ($ids)"
            );
        } else {
            $write->update(
                $this->getMainTable(), array('email_imported' => 1),
                "customer_id = $customer"
            );
        }
    }

    /**
     * Mass delete contacts.
     *
     * @param $contactIds
     *
     * @return Exception|int
     */
    public function massDelete($contactIds)
    {
        try {
            $conn = $this->_getWriteAdapter();
            $num  = $conn->delete(
                $this->getMainTable(),
                array('email_contact_id IN(?)' => $contactIds)
            );

            return $num;
        } catch (Exception $e) {
            return $e;
        }
    }

    /**
     * Mark a contact to be resend.
     *
     * @param $contactIds
     *
     * @return Exception|int
     */
    public function massResend($contactIds)
    {
        try {
            $conn = $this->_getWriteAdapter();
            $num  = $conn->update(
                $this->getMainTable(),
                array('email_imported' => new Zend_Db_Expr('null')),
                array('email_contact_id IN(?)' => $contactIds)
            );

            return $num;
        } catch (Exception $e) {
            return $e;
        }
    }

    /**
     * Prepare recency part of RFM.
     *
     * @param $collection
     *
     * @return array
     */
    public function prepareRecency($collection)
    {
        $select = $collection->getSelect()
            ->reset(Zend_Db_Select::COLUMNS)
            ->reset(Zend_Db_Select::ORDER)
            ->columns(
                array(
                    'last_order_days_ago' => "DATEDIFF(date(NOW()) , date(MAX(created_at)))"
                )
            )->order('last_order_days_ago');

        return $this->getReadConnection()->fetchCol($select);
    }

    /**
     * Prepare frequency part of RFM.
     *
     * @param $collection
     *
     * @return array
     */
    public function prepareFrequency($collection)
    {
        $select = $collection->getSelect()
            ->reset(Zend_Db_Select::COLUMNS)
            ->columns(
                array(
                    'customer_total_orders' => "count(*)",
                )
            )->order('customer_total_orders');

        return $this->getReadConnection()->fetchCol($select);
    }

    /**
     * Prepare monetary part of RFM.
     *
     * @param $collection
     *
     * @return array
     */
    public function prepareMonetary($collection)
    {
        $expr   = $this->getSalesAmountExpression($collection);
        $select = $collection->getSelect()
            ->reset(Zend_Db_Select::COLUMNS)
            ->reset(Zend_Db_Select::ORDER)
            ->columns(
                array(
                    'customer_average_order_value' => new Zend_Db_Expr("SUM({$expr})/count(*)"),
                )
            )->order('customer_average_order_value');

        return $this->getReadConnection()->fetchCol($select);
    }

    /**
     * Get sales amount expression.
     *
     * @param $collection
     *
     * @return string
     */
    public function getSalesAmountExpression($collection)
    {
        $adapter                  = $collection->getConnection();
        $expressionTransferObject = new Varien_Object(
            array(
                'expression' => '%s - %s - %s - (%s - %s - %s)',
                'arguments'  => array(
                    $adapter->getIfNullSql('main_table.base_total_invoiced', 0),
                    $adapter->getIfNullSql('main_table.base_tax_invoiced', 0),
                    $adapter->getIfNullSql(
                        'main_table.base_shipping_invoiced', 0
                    ),
                    $adapter->getIfNullSql('main_table.base_total_refunded', 0),
                    $adapter->getIfNullSql('main_table.base_tax_refunded', 0),
                    $adapter->getIfNullSql(
                        'main_table.base_shipping_refunded', 0
                    ),
                )
            )
        );

        return vsprintf(
            $expressionTransferObject->getExpression(),
            $expressionTransferObject->getArguments()
        );

    }

    /**
     * @param $data
     */
    public function unsubscribe($data)
    {
        //for no data return null
        if (empty($data)) {
            return;
        }

        $write  = $this->_getWriteAdapter();
        $emails = '"' . implode('","', $data) . '"';

        try {
            //un-subscribe from the email contact table.
            $write->update(
                $this->getMainTable(),
                array(
                    'is_subscriber' => new Zend_Db_Expr('null'),
                    'subscriber_status' => Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED,
                    'suppressed' => 1
                ),
                "email IN($emails)"
            );

            // un-subscribe newsletter subscribers
            $newsletterCollection = Mage::getModel('newsletter/subscriber')
                ->getCollection()
                ->addFieldToFilter('subscriber_email', array('in' => $data));

            foreach ($newsletterCollection as $subscriber) {
                Mage::register('unsubscribeEmail', $subscriber->getSubscriberEmail());
                //@codingStandardsIgnoreStart
                $subscriber->setSubscriberStatus(Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED)
                    ->save();
                //@codingStandardsIgnoreEnd
            }
        } catch (Exception $e) {
            Mage::throwException($e->getMessage());
        }
    }

    /**
     * Process unsubscribes from EC, checking whether the user has resubscribed more recently in Magento
     *
     * @param array $unsubscribes
     */
    public function unsubscribeWithResubscriptionCheck(array $unsubscribes)
    {
        $contacts = Mage::getModel('ddg_automation/contact')
            ->getCollection()
            ->addFieldToSelect([
                'email',
                'last_subscribed_at',
            ])
            ->addFieldToFilter('email', ['in' => array_column($unsubscribes, 'email')])
            ->getData();

        // get emails which either have no last_subscribed_at date, or were more recently removed in EC
        $this->unsubscribe($this->filterRecentlyResubscribedEmails($contacts, $unsubscribes));
    }

    /**
     * Insert multiple contacts to table.
     *
     * @param $guests
     */
    public function insertGuest($guests)
    {
        try {
            if (! empty($guests)) {
                $write = $this->_getWriteAdapter();
                $write->insertMultiple($this->getMainTable(), $guests);
            }
        } catch (Exception $e) {
            Mage::throwException($e->getMessage());
        }
    }

    /**
     * Set subscriber imported.
     *
     * @param $ids array
     */
    public function setSubscriberImportedForContacts($ids)
    {
        if (empty($ids))
            return;
        try {
            $write = $this->_getWriteAdapter();
            $ids = implode(', ', $ids);
            $write->update(
                $this->getMainTable(),
                array('subscriber_imported' => Dotdigitalgroup_Email_Model_Contact::EMAIL_SUBSCRIBER_IMPORTED),
                "email_contact_id IN ($ids)"
            );
        } catch (Exception $e) {
            Mage::throwException($e->getMessage());
        }
    }

    /**
     *
     *
     * @param $email
     */
    public function updateSubscriberFromContact($email)
    {
        $conn = $this->getReadConnection();
        $write  = $this->_getWriteAdapter();
        try {
            $write->update(
                $this->getMainTable(),
                array(
                    'is_subscriber' => new Zend_Db_Expr('null'),
                    'subscriber_status' => new Zend_Db_Expr('null'),
                    'subscriber_imported' => new Zend_Db_Expr('null'),
                    'email_imported' => new Zend_Db_Expr('null'),
                ),
                $conn->quoteInto(
                    'email = ?', $email
                )
            );
        } catch (Exception $e) {
            Mage::throwException($e->getMessage());
        }
    }

    /**
     * @param $guests
     */
    public function updateContactsAsGuests($guests)
    {
        $write  = $this->_getWriteAdapter();
        if (! empty($guests)) {
            //make sure the contact are marked as guests if already exists
            $where = array('email IN (?)' => $guests, 'is_guest IS NULL');
            $data = array('is_guest' => 1);
            $write->update($this->getMainTable(), $data, $where);
        }
    }

    /**
     * Process unsubscribes from EC, checking whether the user has resubscribed more recently in Magento
     *
     * @param array $localContacts
     * @param array $unsubscribes
     * @return array
     */
    private function filterRecentlyResubscribedEmails(array $localContacts, array $unsubscribes)
    {
        // get emails which either have no last_subscribed_at date, or were more recently removed in EC
        return array_filter(array_map(function ($email) use ($localContacts) {
            // get corresponding local contact
            $contactKey = array_search($email['email'], array_column($localContacts, 'email'));

            // if there is no local contact, or last subscribed value, continue with unsubscribe
            if ($contactKey === false || is_null($localContacts[$contactKey]['last_subscribed_at'])) {
                return $email['email'];
            }

            // convert both timestamps to DateTime
            $lastSubscribedMagento = new \DateTime($localContacts[$contactKey]['last_subscribed_at'], new \DateTimeZone('UTC'));
            $removedAtEc = new \DateTime($email['removed_at'], new \DateTimeZone('UTC'));

            // user recently resubscribed in Magento, do not unsubscribe them
            if ($lastSubscribedMagento > $removedAtEc) {
                return null;
            }
            return $email['email'];
        }, $unsubscribes));
    }

    /**
     * @param int $batchSize
     * @return $this
     */
    public function populateEmailContactTable($batchSize)
    {
        $customerCollection = Mage::getResourceModel('customer/customer_collection')
            ->addAttributeToSelect('entity_id')
            ->setPageSize(1);
        $customerCollection->getSelect()->order('entity_id ASC');
        $minId = $customerCollection->getSize() ? $customerCollection->getFirstItem()->getId() : 0;

        if ($minId) {
            $customerCollection = Mage::getResourceModel('customer/customer_collection')
                ->addAttributeToSelect('entity_id')
                ->setPageSize(1);
            $customerCollection->getSelect()->order('entity_id DESC');
            $maxId = $customerCollection->getFirstItem()->getId();

            $batchMinId = $minId;
            $batchMaxId = $minId + $batchSize;
            $moreRecords = true;

            while ($moreRecords) {
                $select = $this->_getWriteAdapter()->select()
                    ->from(
                        array('customer' => Mage::getSingleton('core/resource')->getTableName('customer_entity')),
                        array('customer_id' => 'entity_id', 'email', 'website_id', 'store_id')
                    )
                    ->where('customer.entity_id >= ?', $batchMinId)
                    ->where('customer.entity_id < ?', $batchMaxId);

                $insertArray = array('customer_id', 'email', 'website_id', 'store_id');
                $sqlQuery = $select->insertFromSelect($this->getMainTable(), $insertArray, false);
                $this->_getWriteAdapter()->query($sqlQuery);

                $moreRecords = $maxId >= $batchMaxId;
                $batchMinId = $batchMinId + $batchSize;
                $batchMaxId = $batchMaxId + $batchSize;
            }
        }
        return $this;
    }

    /**
     * @param int $batchSize
     * @return $this
     */
    public function populateSubscribersThatAreNotCustomers($batchSize)
    {
        $subscriberCollection = Mage::getResourceModel('newsletter/subscriber_collection')
            ->addFieldToSelect('subscriber_id')
            ->setPageSize(1);
        $subscriberCollection->getSelect()->order('subscriber_id ASC');
        $minId = $subscriberCollection->getSize() ? $subscriberCollection->getFirstItem()->getId() : 0;

        if ($minId) {
            $subscriberCollection = Mage::getResourceModel('newsletter/subscriber_collection')
                ->addFieldToSelect('subscriber_id')
                ->setPageSize(1);
            $subscriberCollection->getSelect()->order('subscriber_id DESC');
            $maxId = $subscriberCollection->getFirstItem()->getId();

            $batchMinId = $minId;
            $batchMaxId = $minId + $batchSize;
            $moreRecords = true;

            while ($moreRecords) {
                $select = $this->_getWriteAdapter()->select()
                    ->from(
                        array('subscriber' => $this->getTable('newsletter/subscriber')),
                        array(
                            'email' => 'subscriber_email',
                            'col2' => new Zend_Db_Expr('1'),
                            'col3' => new Zend_Db_Expr('1'),
                            'store_id'
                        )
                    )
                    ->where('customer_id =?', 0)
                    ->where('subscriber_status =?', 1)
                    ->where('subscriber.subscriber_id >= ?', $batchMinId)
                    ->where('subscriber.subscriber_id < ?', $batchMaxId);

                $insertArray = array('email', 'is_subscriber', 'subscriber_status', 'store_id');
                $sqlQuery = $select->insertFromSelect($this->getMainTable(), $insertArray, false);
                $this->_getWriteAdapter()->query($sqlQuery);

                $moreRecords = $maxId >= $batchMaxId;
                $batchMinId = $batchMinId + $batchSize;
                $batchMaxId = $batchMaxId + $batchSize;
            }
        }
        return $this;
    }

    /**
     * @return $this
     */
    public function updateCustomersThatAreSubscribers()
    {
        $customerIds = Mage::getResourceModel('newsletter/subscriber_collection')
            ->addFieldToFilter('subscriber_status', 1)
            ->addFieldToFilter('customer_id', array('gt' => 0))
            ->getColumnValues('customer_id');

        if (!empty($customerIds)) {
            $customerIds = implode(', ', $customerIds);
            $this->_getWriteAdapter()->update(
                $this->getMainTable(),
                array(
                    'is_subscriber' => new Zend_Db_Expr('1'),
                    'subscriber_status' => new Zend_Db_Expr('1')
                ),
                "customer_id in ($customerIds)"
            );
        }
        return $this;
    }

    /**
     * @return $this
     */
    public function updateContactsWithSegmentsIdsForEnterprise()
    {
        if (Mage::helper('ddg')->isEnterprise()) {
            //customer segment table
            $segmentTable = $this->getTable('enterprise_customersegment/customer');
            //add additional column with segment ids
            $this->_getWriteAdapter()->addColumn(
                $this->getMainTable(),
                'segment_ids',
                'mediumtext'
            );

            //update contact table with customer segment ids
            $this->_getWriteAdapter()->query(
                "update`{$this->getMainTable()}` c,(select customer_id, website_id,
                group_concat(`segment_id` separator ',') as segmentids from `{$segmentTable}` group by customer_id) 
                as s set c.segment_ids = segmentids, c.email_imported = null WHERE s.customer_id= c.customer_id and 
                s.website_id = c.website_id"
            );
        }
        return $this;
    }
}