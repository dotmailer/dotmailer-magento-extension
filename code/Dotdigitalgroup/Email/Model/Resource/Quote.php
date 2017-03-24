<?php

class Dotdigitalgroup_Email_Model_Resource_Quote
    extends Mage_Core_Model_Resource_Db_Abstract
{

    /**
     * Constructor.
     */
    protected function _construct()
    {
        $this->_init('ddg_automation/quote', 'id');
    }

    /**
     * Get sales_flat_quote table description.
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
     * @param null $from
     * @param null $to
     * @return int
     */
    public function resetQuotes($from = null, $to = null)
    {
        $conn = $this->_getWriteAdapter();
        try {
            if ($from && $to) {
                $where = array(
                    'created_at >= ?' => $from . ' 00:00:00',
                    'created_at <= ?' => $to . ' 23:59:59',
                    'imported is ?' => new Zend_Db_Expr('not null')
                );
            } else {
                $where = $conn->quoteInto(
                    'imported is ?', new Zend_Db_Expr('not null')
                );
            }

            $num = $conn->update(
                $this->getMainTable(),
                array(
                    'imported' => new Zend_Db_Expr('null'),
                    'modified' => new Zend_Db_Expr('null')
                ),
                $where
            );

            return $num;
        } catch (Exception $e) {
            Mage::logException($e);
            return 0;
        }
    }

    /**
     * Set imported in bulk query.
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