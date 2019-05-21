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

    /**
     * @param int $batchSize
     */
    public function populateEmailWishlistTable($batchSize)
    {
        $wishlistCollection = Mage::getResourceModel('wishlist/wishlist_collection')
            ->addFieldToSelect('wishlist_id')
            ->setPageSize(1);
        $wishlistCollection->getSelect()->order('wishlist_id ASC');
        $minId = $wishlistCollection->getSize() ? $wishlistCollection->getFirstItem()->getId() : 0;

        if ($minId) {
            $wishlistCollection = Mage::getResourceModel('wishlist/wishlist_collection')
                ->addFieldToSelect('wishlist_id')
                ->setPageSize(1);
            $wishlistCollection->getSelect()->order('wishlist_id DESC');
            $maxId = $wishlistCollection->getFirstItem()->getId();

            $batchMinId = $minId;
            $batchMaxId = $minId + $batchSize;
            $moreRecords = true;

            while ($moreRecords) {
                $select = $this->_getWriteAdapter()->select()
                    ->from(
                        array('wishlist' => $this->getTable('wishlist/wishlist')),
                        array('wishlist_id', 'customer_id', 'created_at' => 'updated_at')
                    )->joinLeft(
                        array('ce' => Mage::getSingleton('core/resource')->getTableName('customer_entity')),
                        "wishlist.customer_id = ce.entity_id",
                        array('store_id')
                    )->joinInner(
                        array('wi' => $this->getTable('wishlist/item')),
                        "wishlist.wishlist_id = wi.wishlist_id",
                        array('item_count' => 'count(wi.wishlist_id)')
                    )
                    ->where('wishlist.wishlist_id >= ?', $batchMinId)
                    ->where('wishlist.wishlist_id < ?', $batchMaxId)
                    ->group('wi.wishlist_id');

                $insertArray = array(
                    'wishlist_id',
                    'customer_id',
                    'created_at',
                    'store_id',
                    'item_count'
                );
                $sqlQuery = $select->insertFromSelect($this->getMainTable(), $insertArray, false);
                $this->_getWriteAdapter()->query($sqlQuery);

                $moreRecords = $maxId >= $batchMaxId;
                $batchMinId = $batchMinId + $batchSize;
                $batchMaxId = $batchMaxId + $batchSize;
            }
        }
    }
}