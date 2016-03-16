<?php

class Dotdigitalgroup_Email_Model_Resource_Quote
    extends Mage_Core_Model_Resource_Db_Abstract
{

    /**
     * constructor.
     */
    protected function _construct()
    {
        $this->_init('ddg_automation/quote', 'id');
    }

    /**
     * get sales_flat_quote table description
     *
     * @return array
     */
    public function getQuoteTableDescription()
    {
        return $this->getReadConnection()->describeTable(
            $this->getTable('sales/quote')
        );
    }

    /**
     * Reset the email quote for re-import.
     *
     * @return int
     */
    public function resetQuotes()
    {
        $conn = $this->_getWriteAdapter();
        try {
            $num = $conn->update(
                $this->getMainTable(),
                array('imported' => new Zend_Db_Expr('null'),
                      'modified' => new Zend_Db_Expr('null'))
            );

            return $num;
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * set imported in bulk query
     *
     * @param $ids
     */
    public function setImported($ids)
    {
        try {
            $write = $this->_getWriteAdapter();
            $tableName = $this->getMainTable();
            $ids = implode(', ', $ids);
            $now = Mage::getSingleton('core/date')->gmtDate();
            $write->update(
                $tableName, array('imported' => 1, 'updated_at' => $now,
                                  'modified' => new Zend_Db_Expr('null')),
                "quote_id IN ($ids)"
            );
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }
}