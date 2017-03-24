<?php

class Dotdigitalgroup_Email_Model_Resource_Campaign
    extends Mage_Core_Model_Resource_Db_Abstract
{

    /**
     * Constructor.
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
                array(
                    'send_status' => Dotdigitalgroup_Email_Model_Campaign::PENDING
                ),
                array('id IN(?)' => $campaignIds)
            );

            return $num;
        } catch (Exception $e) {
            return $e;
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

    /**
     * Set error message.
     *
     * @param $ids
     * @param $message
     */
    public function setMessage($ids, $message)
    {
        try {
            $ids = implode(", ", $ids);
            $now = Mage::getSingleton('core/date')->gmtDate();
            $conn = $this->_getWriteAdapter();
            $conn->update(
                $this->getMainTable(),
                array(
                    'message' => $message,
                    'send_status' => Dotdigitalgroup_Email_Model_Campaign::FAILED,
                    'sent_at' => $now
                ),
                "id in ($ids)"
            );
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * Set error message on given send id
     *
     * @param $sendId
     * @param $message
     */
    public function setMessageWithSendId($sendId, $message)
    {
        try {
            $now = Mage::getSingleton('core/date')->gmtDate();
            $conn = $this->_getWriteAdapter();
            $conn->update(
                $this->getMainTable(),
                array(
                    'message' => $message,
                    'send_status' => Dotdigitalgroup_Email_Model_Campaign::FAILED,
                    'sent_at' => $now
                ),
                array('send_id = ?' => $sendId)
            );
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * Set sent.
     *
     * @param bool $sendId
     */
    public function setSent($sendId)
    {
        try {
            $now = Mage::getSingleton('core/date')->gmtDate();
            $bind = array(
                'send_status' => Dotdigitalgroup_Email_Model_Campaign::SENT,
                'sent_at' => $now
            );
            $conn = $this->_getWriteAdapter();
            $conn->update(
                $this->getMainTable(),
                $bind,
                array('send_id = ?' => $sendId)
            );
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * Set processing.
     *
     * @param $ids
     * @param bool $sendId
     */
    public function setProcessing($ids, $sendId)
    {
        try {
            $ids = implode(', ', $ids);
            $bind = array(
                'send_status' => Dotdigitalgroup_Email_Model_Campaign::PROCESSING,
                'send_id' => $sendId
            );
            $conn = $this->_getWriteAdapter();
            $conn->update(
                $this->getMainTable(),
                $bind,
                "id in ($ids)"
            );
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }
}