<?php

class Dotdigitalgroup_Email_Model_Abandoned extends Mage_Core_Model_Abstract
{

    /**
     * Constructor.
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('ddg_automation/abandoned');
    }

    /**
     * @return $this
     */
    protected function _beforeSave()
    {
        parent::_beforeSave();
        $now = Mage::getSingleton('core/date')->gmtDate();
        if ($this->isObjectNew()) {
            $this->setCreatedAt($now);
        } else {
            $this->setUpdatedAt($now);
        }

        return $this;
    }

    /**
     * Load abandoned by quote id.
     *
     * @param $quoteId
     * @return $this
     */
    public function loadByQuoteId($quoteId)
    {
        $collection = $this->getCollection()
            ->addFieldToFilter('quote_id', $quoteId)
            ->setPageSize(1);

        return $collection->getFirstItem();
    }

}