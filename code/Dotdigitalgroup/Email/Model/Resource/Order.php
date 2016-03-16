<?php

class Dotdigitalgroup_Email_Model_Resource_Order
    extends Mage_Core_Model_Resource_Db_Abstract
{

    /**
     * cosntructor.
     */
    protected function _construct()
    {
        $this->_init('ddg_automation/order', 'email_order_id');
    }

    /**
     * get sales_flat_order table description
     *
     * @return array
     */
    public function getOrderTableDescription()
    {
        return $this->getReadConnection()->describeTable(
            $this->getTable('sales/order')
        );
    }

    /**
     * Reset the email order for reimport.
     *
     * @return int
     */
    public function resetOrders()
    {
        try {
            $conn = $this->_getWriteAdapter();
            $num  = $conn->update(
                $this->getMainTable(),
                array('email_imported' => new Zend_Db_Expr('null'),
                      'modified'       => new Zend_Db_Expr('null')),
                $conn->quoteInto(
                    'email_imported is ?', new Zend_Db_Expr('not null')
                )
            );

            return $num;
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }
}