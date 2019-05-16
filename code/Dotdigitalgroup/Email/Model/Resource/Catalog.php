<?php

class Dotdigitalgroup_Email_Model_Resource_Catalog
    extends Mage_Core_Model_Resource_Db_Abstract
{

    /**
     * Constructor.
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
            return 0;
        }
    }

    /**
     * Set imported in bulk query. if modified true then set modified to null in bulk query.
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

    /**
     * Set modified if already imported
     *
     * @param $ids
     */
    public function setModified($ids)
    {
        try {
            $write     = $this->_getWriteAdapter();
            $tableName = $this->getMainTable();
            $write->update(
                $tableName,
                array('modified' => 1),
                array(
                    $write->quoteInto("product_id IN (?)", $ids),
                    $write->quoteInto("imported = ?", 1)
                )
            );
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * @param int $batchSize
     */
    public function populateEmailCatalogTable($batchSize)
    {
        $catalogCollection = Mage::getResourceModel('catalog/product_collection')
            ->addAttributeToSelect('entity_id')
            ->setPageSize(1);
        $catalogCollection->getSelect()->order('entity_id ASC');
        $minId = $catalogCollection->getSize() ? $catalogCollection->getFirstItem()->getId() : 0;

        if ($minId) {
            $catalogCollection = Mage::getResourceModel('catalog/product_collection')
                ->addAttributeToSelect('entity_id')
                ->setPageSize(1);
            $catalogCollection->getSelect()->order('entity_id DESC');
            $maxId = $catalogCollection->getFirstItem()->getId();

            $batchMinId = $minId;
            $batchMaxId = $minId + $batchSize;
            $moreRecords = true;

            while ($moreRecords) {
                $select = $this->_getWriteAdapter()->select()
                    ->from(
                        array('catalog' => $this->getTable('catalog/product')),
                        array(
                            'product_id' => 'catalog.entity_id',
                            'created_at' => 'catalog.created_at'
                        )
                    )
                    ->where('catalog.entity_id >= ?', $batchMinId)
                    ->where('catalog.entity_id < ?', $batchMaxId);

                $insertArray = array('product_id', 'created_at');
                $sqlQuery = $select->insertFromSelect($this->getMainTable(), $insertArray, false);
                $this->_getWriteAdapter()->query($sqlQuery);

                $moreRecords = $maxId >= $batchMaxId;
                $batchMinId = $batchMinId + $batchSize;
                $batchMaxId = $batchMaxId + $batchSize;
            }
        }
    }
}