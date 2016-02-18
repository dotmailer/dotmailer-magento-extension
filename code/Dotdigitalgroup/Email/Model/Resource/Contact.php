<?php

class Dotdigitalgroup_Email_Model_Resource_Contact extends Mage_Core_Model_Mysql4_Abstract
{

	/**
	 * constructor.
	 */
	protected  function _construct()
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
        try{
            $conn = $this->getReadConnection();
            $num = $conn->update($this->getMainTable(),
                array('contact_id' => new Zend_Db_Expr('null')),
                $conn->quoteInto('contact_id is ?', new Zend_Db_Expr('not null'))
            );
            return $num;
        }catch (Exception $e){
            Mage::logException($e);
            Mage::helper('ddg')->rayLog($e);
        }
    }

    /**
     * Reset the imported contacts
     * @return int
     */
    public function resetAllContacts()
    {
        try{
            $conn = $this->_getWriteAdapter();
            $num = $conn->update($this->getMainTable(),
                array('email_imported' => new Zend_Db_Expr('null')),
                $conn->quoteInto('email_imported is ?', new Zend_Db_Expr('not null'))
            );
            return $num;
        }catch (Exception $e){
            Mage::logException($e);
            Mage::helper('ddg')->rayLog($e);
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
            $num = $conn->update(
                $this->getMainTable(),
                array('subscriber_imported' => new Zend_Db_Expr( 'null' ) ),
                $conn->quoteInto('subscriber_imported is ?', new Zend_Db_Expr('not null')));
            return $num;
        } catch ( Exception $e ) {
            Mage::logException($e);
            Mage::helper('ddg')->sendRaygunException($e);
        }
    }

    /**
     * Reset the imported contacts as guest
     * @return int
     */
    public function resetAllGuestContacts()
    {
        try {
            $conn = $this->_getWriteAdapter();
            $where = array();
            $where[] = $conn->quoteInto('email_imported is ?', new Zend_Db_Expr('not null'));
            $where[] = $conn->quoteInto('is_guest is ?', new Zend_Db_Expr('not null'));

            $num = $conn->update($this->getMainTable(),
                array('email_imported' => new Zend_Db_Expr('null')),
                $where
            );
            return $num;
        } catch (Exception $e) {
            Mage::logException($e);
            Mage::helper('ddg')->rayLog($e);
        }
    }

    /**
     * populate and cleanup
     */
    public function populateAndCleanup()
    {
        $write = $this->_getWriteAdapter();
        $contactTable = $this->getMainTable();
        $select = $write->select();

        //populate customers to email_contact
        $emailContacts = Mage::getModel('ddg_automation/contact')
            ->getCollection()
            ->getColumnValues('customer_id');
        $emailContacts = implode(',', $emailContacts);

        $select
            ->from(
                array('customer' => $this->getReadConnection()->getTableName($this->getTable('customer/customer'))),
                array('customer_id' => 'entity_id','email','website_id','store_id')
            )
            ->where("entity_id not in ($emailContacts)");

        $insertArray = array('customer_id','email','website_id','store_id');
        $sqlQuery = $select->insertFromSelect($contactTable, $insertArray, false);
        $write->query($sqlQuery);

        //remove contact with customer id set and no customer
        $select->reset()
            ->from(
                array('c' => $contactTable),
                array('c.customer_id')
            )
            ->joinLeft(
                array('e' => $this->getReadConnection()->getTableName($this->getTable('customer/customer'))),
                "c.customer_id = e.entity_id"
            )
            ->where('e.entity_id is NULL');
        $deleteSql = $select->deleteFromSelect('c');
        $write->query($deleteSql);
    }

    /**
     * Re-set all tables
     */
    public function resetAllTables()
    {
        $conn = $this->_getWriteAdapter();
        try{
            //remove dotmailer code from core_resource table
            $cond = $conn->quoteInto('code = ?', 'email_connector_setup');
            $conn->delete($this->getReadConnection()->getTableName('core_resource'), $cond);

            //clean cache
            Mage::app()->getCacheInstance()->flush();

        }catch (Exception $e){
            Mage::logException($e);
        }
    }

    public function update($customer)
    {
        $write = $this->_getWriteAdapter();
        if(is_array($customer)){
            $ids = implode(', ', $customer);
            $write->update($this->getMainTable(), array('email_imported' => 1), "customer_id IN ($ids)");
        }
        else
            $write->update($this->getMainTable(), array('email_imported' => 1), "customer_id = $customer");
    }

    /**
     * Mass delete contacts.
     *
     * @param $contactIds
     * @return Exception|int
     */
    public function massDelete($contactIds)
    {
        try {
            $conn = $this->_getWriteAdapter();
            $num = $conn->delete(
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
     * @return Exception|int
     */
    public function massResend($contactIds)
    {
        try {
            $conn = $this->_getWriteAdapter();
            $num = $conn->update(
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
     * prepare recency part of RFM
     *
     * @param $collection
     * @return array
     */
    public function prepareRecency($collection)
    {
        $select = $collection->getSelect()
            ->reset(Zend_Db_Select::COLUMNS)
            ->reset(Zend_Db_Select::ORDER)
            ->columns(array(
                'last_order_days_ago' => "DATEDIFF(date(NOW()) , date(MAX(created_at)))"
            ))->order('last_order_days_ago');
        return $this->getReadConnection()->fetchCol($select);
    }

    /**
     * prepare frequency part of RFM
     *
     * @param $collection
     * @return array
     */
    public function prepareFrequency($collection)
    {
        $select = $collection->getSelect()
            ->reset(Zend_Db_Select::COLUMNS)
            ->columns(array(
                'customer_total_orders' => "count(*)",
            ))->order('customer_total_orders');
        return $this->getReadConnection()->fetchCol($select);
    }

    /**
     * prepare monetary part of RFM
     *
     * @param $collection
     * @return array
     */
    public function prepareMonetary($collection)
    {
        $expr = $this->getSalesAmountExpression($collection);
        $select = $collection->getSelect()
            ->reset(Zend_Db_Select::COLUMNS)
            ->reset(Zend_Db_Select::ORDER)
            ->columns(array(
                'customer_average_order_value' => "SUM({$expr})/count(*)",
            ))->order('customer_average_order_value');
        return $this->getReadConnection()->fetchCol($select);
    }

    /**
     * get sales amount expression
     *
     * @param $collection
     * @return string
     */
    public function getSalesAmountExpression($collection)
    {
        $adapter = $collection->getConnection();
        $expressionTransferObject = new Varien_Object(array(
            'expression' => '%s - %s - %s - (%s - %s - %s)',
            'arguments' => array(
                $adapter->getIfNullSql('main_table.base_total_invoiced', 0),
                $adapter->getIfNullSql('main_table.base_tax_invoiced', 0),
                $adapter->getIfNullSql('main_table.base_shipping_invoiced', 0),
                $adapter->getIfNullSql('main_table.base_total_refunded', 0),
                $adapter->getIfNullSql('main_table.base_tax_refunded', 0),
                $adapter->getIfNullSql('main_table.base_shipping_refunded', 0),
            )
        ));

        return vsprintf(
            $expressionTransferObject->getExpression(),
            $expressionTransferObject->getArguments()
        );

    }

    public function unsubscribe($data)
    {
        $write = $this->_getWriteAdapter();
        $emails = '"' . implode('","', $data) . '"';

        try{
            //un-subscribe from the email contact table.
	        $whereCondition = $write->quoteInto('email IN (?)', $emails);

	        $write->update(
                $this->getMainTable(),
                array(
                    'is_subscriber' => new Zend_Db_Expr('null'),
                    'suppressed' => '1'
                ),
	            $whereCondition
            );

	        // un-subscribe newsletter subscribers
			$newsletterCollection = Mage::getModel('newsletter/subscriber')->getCollection()
				->addFieldToFilter('subscriber_email', array('in' => $emails));

	        foreach ( $newsletterCollection as $subscriber ) {
		        $subscriber->setSubscriberStatus( Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED)
			        ->save();
			}

        }catch (Exception $e){
            Mage::throwException($e->getMessage());
            Mage::logException($e);
        }
    }
}