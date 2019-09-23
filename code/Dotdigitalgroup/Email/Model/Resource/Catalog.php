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
                $where = [
                    '(created_at >= ?' .$from. ' 00:00:00' .' AND created_at <= ?'. $to . ' 23:59:59)',
                    'AND (last_imported_at IS NOT NULL OR processed = 1)'
                ];
            } else {
                $where[] = 'last_imported_at IS NOT NULL OR processed= 1';
            }
            $num = $conn->update(
                $this->getMainTable(),
                array(
                    'last_imported_at' => new Zend_Db_Expr('null'),
                    'processed' => 0
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
     */
    public function setImported($ids)
    {
        try {
            $write     = $this->_getWriteAdapter();
            $tableName = $this->getMainTable();
            $ids       = implode(', ', $ids);
            $now       = Mage::getSingleton('core/date')->gmtDate();
            $bind = array('last_imported_at' => $now, 'updated_at' => $now);
            $write->update(
                $tableName,
                $bind,
                "product_id IN ($ids)"
            );
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * Set processed in bulk query.
     *
     * @param $ids
     */
    public function setProcessed($ids)
    {
        try {
            $write = $this->_getWriteAdapter();
            $tableName = $this->getMainTable();
            $ids = implode(', ', $ids);
            $now       = Mage::getSingleton('core/date')->gmtDate();
            $bind = array('processed' => 1, 'updated_at' => $now);
            $write->update(
                $tableName,
                $bind,
                "product_id IN ($ids)"
            );
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * Set processed in bulk query.
     *
     * @param $ids
     */
    public function setUnProcessed($ids)
    {
        try {
            $write = $this->_getWriteAdapter();
            $tableName = $this->getMainTable();
            $ids = implode(', ', $ids);
            $now       = Mage::getSingleton('core/date')->gmtDate();
            $bind = array('processed' => 0, 'updated_at' => $now);
            $write->update(
                $tableName,
                $bind,
                "product_id IN ($ids)"
            );
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * Resets Last Imported At after the 6.4.21 Upgrade
     * @param $ids
     */
    public function resetLastImportedAt($ids)
    {
        try {
            $write = $this->_getWriteAdapter();
            $tableName = $this->getMainTable();
            $ids = implode(', ', $ids);
            $bind = array('last_imported_at' => new Zend_Db_Expr('null'));
            $write->update(
                $tableName,
                $bind,
                "product_id IN ($ids)"
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