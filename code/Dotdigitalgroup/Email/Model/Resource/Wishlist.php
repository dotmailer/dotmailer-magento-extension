<?php

class Dotdigitalgroup_Email_Model_Resource_Wishlist
    extends Mage_Core_Model_Resource_Db_Abstract
{

    /**
     * Constructor.
     */
    protected function _construct()
    {
        $this->_init('ddg_automation/wishlist', 'id');
    }

    /**
     * Reset the email reviews for re-import.
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
                    'wishlist_imported is ?' => new Zend_Db_Expr('not null')
                );
            } else {
                $where = $conn->quoteInto(
                    'wishlist_imported is ?', new Zend_Db_Expr('not null')
                );
            }

            $num = $conn->update(
                $this->getMainTable(),
                array(
                    'wishlist_imported' => new Zend_Db_Expr('null'),
                    'wishlist_modified' => new Zend_Db_Expr('null')
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
     * @param $modified
     */
    public function setImported($ids, $modified = false)
    {
        try {
            $write = $this->_getWriteAdapter();
            $tableName = $this->getMainTable();
            $ids = implode(', ', $ids);
            $now = Mage::getSingleton('core/date')->gmtDate();
            if ($modified) {
                $write->update(
                    $tableName,
                    array('wishlist_modified' => new Zend_Db_Expr('null'),
                          'updated_at'        => $now), "wishlist_id IN ($ids)"
                );
            } else {
                $write->update(
                    $tableName,
                    array('wishlist_imported' => 1, 'updated_at' => $now),
                    "wishlist_id IN ($ids)"
                );
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }
}