<?php

class Dotdigitalgroup_Email_Model_Resource_Campaign_Collection
    extends Mage_Core_Model_Resource_Db_Collection_Abstract
{

    /**
     * Constructor.
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('ddg_automation/campaign');
    }

    /**
     * @param $email
     *
     * @return int
     */
    public function getNumberOfProcessingAcCampaignsForContact($email)
    {
        return $this->addFieldToFilter('email', $email)
            ->addFieldToFilter('event_name', Dotdigitalgroup_Email_Model_Campaign::CAMPAIGN_EVENT_LOST_BASKET)
            ->addFieldToFilter('send_status', Dotdigitalgroup_Email_Model_Campaign::PROCESSING)
            ->getSize();
    }
}
