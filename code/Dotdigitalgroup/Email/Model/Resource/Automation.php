<?php

class Dotdigitalgroup_Email_Model_Resource_Automation
    extends Mage_Core_Model_Resource_Db_Abstract
{

    /**
     * constructor.
     */
    protected function _construct()
    {
        $this->_init('ddg_automation/automation', 'id');

    }

    public function updateContacts($contacts, $programStatus, $programMessage)
    {
        $conn = $this->_getWriteAdapter();
        try {
            $contactIds = array_keys($contacts);
            $bind       = array(
                'enrolment_status' => $programStatus,
                'message'          => $programMessage,
                'updated_at'       => Mage::getSingleton('core/date')->gmtDate()
            );
            $where      = array('id IN(?)' => $contactIds);
            $num        = $conn->update(
                $this->getMainTable(),
                $bind,
                $where
            );

            return $num;
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * mass delete
     *
     * @param $automationIds
     *
     * @return Exception|int
     */
    public function massDelete($automationIds)
    {
        try {
            $conn = $this->_getWriteAdapter();
            $num  = $conn->delete(
                $this->getMainTable(),
                array('id IN(?)' => $automationIds)
            );

            return $num;
        } catch (Exception $e) {
            return $e;
        }
    }

    /**
     * Mark for resend.
     *
     * @param $automationIds
     *
     * @return int
     */
    public function massResend($automationIds)
    {
        try {
            $conn = $this->_getWriteAdapter();
            $num  = $conn->update(
                $this->getMainTable(),
                array('enrolment_status' => Dotdigitalgroup_Email_Model_Automation::AUTOMATION_STATUS_PENDING),
                array('id IN(?)' => $automationIds)
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
}