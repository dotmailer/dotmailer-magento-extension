<?php

class Dotdigitalgroup_Email_Model_Resource_Catalog
    extends Mage_Core_Model_Resource_Db_Abstract
{

    /**
     * constructor.
     */
    protected function _construct()
    {
        $this->_init('ddg_automation/catalog', 'id');

    }

    /**
     * Reset for re-import.
     *
     * @param null $from
     * @param null $to
     * @return int
     */
    public function reset($from = null, $to = null)
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
        }
    }

    /**
     * set imported in bulk query. if modified true then set modified to null in bulk query.
     *
     * @param $ids
     * @param $modified
     */
    public function setImported($ids, $modified = false)
    {
        try {
            $write     = $this->_getWriteAdapter();
            $tableName = $this->getMainTable();
            $ids       = implode(', ', $ids);
            $now       = Mage::getSingleton('core/date')->gmtDate();
            if ($modified) {
                $write->update(
                    $tableName, array('modified'   => new Zend_Db_Expr('null'),
                                      'updated_at' => $now),
                    "product_id IN ($ids)"
                );
            } else {
                $write->update(
                    $tableName, array('imported' => 1, 'updated_at' => $now),
                    "product_id IN ($ids)"
                );
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }
}