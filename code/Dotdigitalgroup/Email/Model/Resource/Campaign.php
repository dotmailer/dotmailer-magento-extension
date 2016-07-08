<?php

class Dotdigitalgroup_Email_Model_Resource_Campaign
    extends Mage_Core_Model_Resource_Db_Abstract
{

    /**
     * constructor.
     */
    protected function _construct()
    {
        $this->_init('ddg_automation/campaign', 'id');

    }

    /**
     * Delete mass campaigns.
     *
     * @param $campaignIds
     *
     * @return Exception|int
     */
    public function massDelete($campaignIds)
    {
        try {
            $conn = $this->_getWriteAdapter();
            $num  = $conn->delete(
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
     *
     * @return Exception|int
     */
    public function massResend($campaignIds)
    {
        try {
            $conn = $this->_getWriteAdapter();
            $num  = $conn->update(
                $this->getMainTable(),
                array('is_sent' => new Zend_Db_Expr('null')),
                array('id IN(?)' => $campaignIds)
            );

            return $num;
        } catch (Exception $e) {
            return $e;
        }
    }

    /**
     * delete completed records older then 30 days
     *
     * @return Exception|int
     */
    public function cleanup()
    {
        try {
            $date = Mage::app()->getLocale()->date()->subDay(30)->toString('YYYY-MM-dd HH:mm:ss');
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

    /**
     * Set error message
     *
     * @param $campaignId
     * @param $message
     */
    public function setMessage($campaignId, $message)
    {
        try {
            $now = Mage::getSingleton('core/date')->gmtDate();
            $conn = $this->_getWriteAdapter();
            $conn->update(
                $this->getMainTable(),
                array(
                    'message' => $message,
                    'is_sent' => 1,
                    'sent_at' => $now
                ),
                array('campaign_id = ?' => $campaignId)
            );
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * Set sent
     *
     * @param $campaignId
     * @param bool $sendId
     */
    public function setSent($campaignId, $sendId = false)
    {
        try {
            $now = Mage::getSingleton('core/date')->gmtDate();
            $bind = array(
                'is_sent' => 1,
                'sent_at' => $now
            );
            if ($sendId) {
                $bind['send_id'] = $sendId;
            }
            $conn = $this->_getWriteAdapter();
            $conn->update(
                $this->getMainTable(),
                $bind,
                array('campaign_id = ?' => $campaignId)
            );
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }
}