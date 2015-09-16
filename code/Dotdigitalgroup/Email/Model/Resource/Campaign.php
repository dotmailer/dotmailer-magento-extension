<?php

class Dotdigitalgroup_Email_Model_Resource_Campaign extends Mage_Core_Model_Resource_Db_Abstract
{
	/**
	 * constructor.
	 */
	protected  function _construct()
    {
        $this->_init('ddg_automation/campaign', 'id');

    }

    /**
     * Delete mass campaigns.
     *
     * @param $campaignIds
     * @return Exception|int
     */
    public function massDelete($campaignIds)
    {
        try {
            $conn =$this->_getWriteAdapter();
            $num = $conn->delete(
                $this->getMainTable(),
                array('id IN(?)' => $campaignIds)
            );
            return $num;
        } catch (Exception $e) {
            return $e;
        }
    }

    /**
     * Mass mark for resend campaigns.
     *
     * @param $campaignIds
     * @return Exception|int
     */
    public function massResend($campaignIds)
    {
        try {
            $conn = $this->_getWriteAdapter();
            $num = $conn->update(
                $this->getMainTable(),
                array('is_sent' => new Zend_Db_Expr('null')),
                array('id IN(?)' => $campaignIds)
            );
            return $num;
        } catch (Exception $e) {
            return $e;
        }
    }
}