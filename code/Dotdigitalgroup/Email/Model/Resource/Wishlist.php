<?php

class Dotdigitalgroup_Email_Model_Resource_Wishlist extends Mage_Core_Model_Mysql4_Abstract
{
    /**
     * constructor.
     */
    protected  function _construct()
    {
        $this->_init('ddg_automation/wishlist', 'id');

    }

    /**
     * Reset the email reviews for re-import.
     *
     * @return int
     */
    public function reset()
    {
        $conn = $this->_getWriteAdapter();
        try{
            $num = $conn->update($this->getMainTable(),
                array('wishlist_imported' => new Zend_Db_Expr('null'), 'wishlist_modified' => new Zend_Db_Expr('null'))
            );
            return $num;
        }catch (Exception $e){
            Mage::logException($e);
        }
    }

    /**
     * set imported in bulk query
     *
     * @param $ids
     * @param $modified
     */
    public function setImported($ids, $modified = false)
    {
        try{
            $write = $this->_getWriteAdapter();
            $tableName = $this->getMainTable();
            $ids = implode(', ', $ids);
            $now = Mage::getSingleton('core/date')->gmtDate();
            if($modified)
                $write->update($tableName, array('wishlist_modified' => new Zend_Db_Expr('null'), 'updated_at' => $now), "wishlist_id IN ($ids)");
            else
                $write->update($tableName, array('wishlist_imported' => 1, 'updated_at' => $now), "wishlist_id IN ($ids)");
        }catch (Exception $e){
            Mage::logException($e);
        }
    }
}