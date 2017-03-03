<?php

class Dotdigitalgroup_Email_Model_Resource_Importer
    extends Mage_Core_Model_Resource_Db_Abstract
{

    /**
     * Constructor.
     */
    protected function _construct()
    {
        $this->_init('ddg_automation/importer', 'id');
    }

    /**
     * Mark a contact to be resend.
     *
     * @param $ids
     *
     * @return int
     */
    public function massResend($ids)
    {
        try {
            $conn = $this->_getWriteAdapter();
            $num  = $conn->update(
                $this->getMainTable(),
                array('import_status' => 0),
                array('id IN(?)' => $ids)
            );

            return $num;
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Mass delete contacts.
     *
     * @param $ids
     *
     * @return int
     */
    public function massDelete($ids)
    {
        try {
            $conn = $this->_getWriteAdapter();
            $num  = $conn->delete(
                $this->getMainTable(),
                array('id IN(?)' => $ids)
            );

            return $num;
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Delete completed records older then 30 days.
     *
     * @return Exception|int
     */
    public function cleanup()
    {
        try {
            //@codingStandardsIgnoreStart
            $date = Mage::app()->getLocale()->date()->subDay(30)
                ->toString('YYYY-MM-dd HH:mm:ss');
            //@codingStandardsIgnoreEnd
            $conn = $this->_getWriteAdapter();
            $num = $conn->delete(
                $this->getMainTable(),
                array('created_at < ?' => $date)
            );

            return $num;
        } catch (Exception $e) {
            return $e;
        }
    }
}