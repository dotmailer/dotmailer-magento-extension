<?php

class Dotdigitalgroup_Email_Model_Config extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('ddg_automation/config');
    }

	/**
	 * @return $this|Mage_Core_Model_Abstract
	 */
	protected function _beforeSave()
	{
		$now = Mage::getSingleton('core/date')->gmtDate();
		if ($this->isObjectNew()) {
			$this->setCreatedAt($now);
		}
		$this->setUpdatedAt($now);

		return parent::_beforeSave();
	}


	/**
	 * Get the date value for the hour trigger.
	 * Reset the api calls for more than an hour.
	 * @return bool
	 */
	public function getHourTrigger()
	{
		$config =  $this->getCollection()
		                ->addFieldToFilter('path', Dotdigitalgroup_Email_Helper_Config::CONNECTOR_EMAIL_CONFIG_HOUR_TRIGGER);
		//found the config value
		if ($config->getSize()) {
			return $configData = $config->getFirstItem()->getValue();
		}

		return false;
	}


	/**
	 * Get the value for configuration path.
	 * @param $path
	 *
	 * @return mixed
	 */
	public function getValueByPath( $path )
	{
		$collection = $this->getCollection()
			->addFieldToFilter('path', $path)
			->setPageSize(1);

		//found the item
		if ($collection->getSize()) {
			return $collection->getFirstItem();
		}

		$this->setPath($path);
		return $this;
	}

}