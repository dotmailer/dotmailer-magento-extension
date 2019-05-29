<?php

class Dotdigitalgroup_Email_Model_Resource_Review
    extends Mage_Core_Model_Resource_Db_Abstract
{

    /**
     * Constructor.
     */
    protected function _construct()
    {
        $this->_init('ddg_automation/review', 'id');
    }

    /**
     * Reset the email reviews for reimport.
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
                    'review_imported is ?' => new Zend_Db_Expr('not null')
                );
            } else {
                $where = $conn->quoteInto(
                    'review_imported is ?', new Zend_Db_Expr('not null')
                );
            }

            $num = $conn->update(
                $this->getMainTable(),
                array('review_imported' => new Zend_Db_Expr('null')),
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
                $tableName, array('review_imported' => 1, 'updated_at' => $now),
                "review_id IN ($ids)"
            );
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * @param int $batchSize
     * @return void
     */
    public function populateEmailReviewTable($batchSize)
    {
        if (!Mage::helper('ddg/moduleChecker')->isReviewModuleAvailable()) {
            return null;
        }
        $reviewCollection = Mage::getResourceModel('review/review_collection')
            ->addFieldToSelect('review_id')
            ->setPageSize(1);
        $reviewCollection->getSelect()->order('review_id ASC');
        $minId = $reviewCollection->getSize() ? $reviewCollection->getFirstItem()->getId() : 0;

        if ($minId) {
            $reviewCollection = Mage::getResourceModel('review/review_collection')
                ->addFieldToSelect('review_id')
                ->setPageSize(1);
            $reviewCollection->getSelect()->order('review_id DESC');
            $maxId = $reviewCollection->getFirstItem()->getId();

            $batchMinId = $minId;
            $batchMaxId = $minId + $batchSize;
            $moreRecords = true;

            while ($moreRecords) {
                $inCond = $this->_getWriteAdapter()->prepareSqlCondition(
                    'review_detail.customer_id', array('notnull' => true)
                );
                $select = $this->_getWriteAdapter()->select()
                    ->from(
                        array('review' => $this->getTable('review/review')),
                        array(
                            'review_id' => 'review.review_id',
                            'created_at' => 'review.created_at'
                        )
                    )
                    ->joinLeft(
                        array('review_detail' => $this->getTable('review/review_detail')),
                        "review_detail.review_id = review.review_id",
                        array(
                            'store_id' => 'review_detail.store_id',
                            'customer_id' => 'review_detail.customer_id'
                        )
                    )
                    ->where($inCond)
                    ->where('review.review_id >= ?', $batchMinId)
                    ->where('review.review_id < ?', $batchMaxId);

                $insertArray = array('review_id', 'created_at', 'store_id', 'customer_id');
                $sqlQuery = $select->insertFromSelect($this->getMainTable(), $insertArray, false);
                $this->_getWriteAdapter()->query($sqlQuery);

                $moreRecords = $maxId >= $batchMaxId;
                $batchMinId = $batchMinId + $batchSize;
                $batchMaxId = $batchMaxId + $batchSize;
            }
        }
    }
}