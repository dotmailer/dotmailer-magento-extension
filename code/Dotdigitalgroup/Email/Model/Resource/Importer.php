<?php

class Dotdigitalgroup_Email_Model_Resource_Importer extends Mage_Core_Model_Resource_Db_Abstract
{
    /**
     * cosntructor.
     */
    protected function _construct()
    {
        $this->_init('ddg_automation/importer', 'id');
    }

    /**
     * Mark a contact to be resend.
     *
     * @param $ids
     * @return Exception|int
     */
    public function massResend($ids)
    {
        try {
            $conn = $this->_getWriteAdapter();
            $num = $conn->update($this->getMainTable(),
                array('import_status' => 0),
                array('id IN(?)' => $ids)
            );
            return $num;
        } catch (Exception $e) {
            return $e;
        }
    }

    /**
     * Mass delete contacts.
     *
     * @param $ids
     * @return Exception|int
     */
    public function massDelete($ids)
    {
        try {
            $conn = $this->_getWriteAdapter();
            $num = $conn->delete($this->getMainTable(),
                array('id IN(?)' => $ids)
            );
            return $num;
        } catch (Exception $e) {
            return $e;
        }
    }
}