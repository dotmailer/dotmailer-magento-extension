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
            //remove dotmailer code from core_resource table
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
                    'customer_average_order_value' => "SUM({$expr})/count(*)",
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
     * Insert multiple contacts to table.
     *
     * @param $data
     */
    public function insertGuest($data)
    {
        try {
            $emailsExistInTable = Mage::getModel('ddg_automation/contact')
                ->getCollection()
                ->addFieldToFilter('email', array('in' => $data))
                ->getColumnValues('email');

            foreach ($emailsExistInTable as $duplicate) {
                unset($data[$duplicate]);
            }

            $write = $this->_getWriteAdapter();
            $write->insertMultiple($this->getMainTable(), $data);
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
}