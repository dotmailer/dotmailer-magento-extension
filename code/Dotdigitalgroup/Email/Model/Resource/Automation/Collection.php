<?php

class Dotdigitalgroup_Email_Model_Resource_Automation_Collection
    extends Mage_Core_Model_Resource_Db_Collection_Abstract
{

    /**
     * Constructor.
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('ddg_automation/automation');
    }


    /**
     * @param $website
     *
     * @return $this
     */
    public function addWebsiteFilter($website)
    {
        $this->addFilter('website_id', $website);

        return $this;
    }

}