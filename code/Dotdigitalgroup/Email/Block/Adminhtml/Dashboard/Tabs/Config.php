<?php

class Dotdigitalgroup_Email_Block_Adminhtml_Dashboard_Tabs_Config extends  Mage_Adminhtml_Block_Dashboard_Bar
{
	/**
	 * set template
	 *
	 * @throws Exception
	 */
	public function __construct()
	{
		parent::_construct();
		$this->setTemplate('connector/dashboard/tabs/config.phtml');
	}

	/**
	 * Prepare the layout. set child blocks
	 *
	 * @return Mage_Core_Block_Abstract|void
	 * @throws Exception
	 */
	protected function _prepareLayout()
	{

	}

	/**
	 * get Tab content title
	 *
	 * @return string
	 */
	public function getTitle()
	{
		return 'Dashboard';
	}

	/**
	 * Load the config data.
	 * @return array
	 */
	public function getConfigData()
	{
		//config data collection
		$collection = Mage::getModel('email_connector/config')->getCollection()
			->addFieldToFilter('path', array('neq' => 'connector_api_hour_trigger'))
			->addFieldToFilter('is_api', true);

		$data = $collection->getData();

		return $data;
	}

	/**
	 * Get the time trigger from collection.
	 * @return mixed
	 */
	public function getTimeTrigger()
	{
		$timeData = Mage::getModel('email_connector/config')->getCollection()
			->addFieldToFilter('path', Dotdigitalgroup_Email_Helper_Config::CONNECTOR_EMAIL_CONFIG_HOUR_TRIGGER)
			->setPageSize(1)
			->getFirstItem();

		return $timeData->getValue();
	}

	/**
	 * Format the date string.
	 * @param $date
	 *
	 * @return string
	 */
	public function formatDateString($date)
	{
		if ($date) {
			$date = new Zend_Date($date);
			return $date->toString(Zend_Date::DATETIME);
		}
		return 'First Time Run';
	}

	/**
	 * get column width
	 *
	 * @return string
	 */
	public function getColumnWidth()
	{
		return "620px";
	}


	/**
	 * Get doc desription for the Apiconnector reflection class.
	 * reutrn the comment for each method.
	 * @param $method
	 *
	 * @return string
	 */
	public function getDocDocument($method)
	{
		//reflection class for client
		$rc = new ReflectionClass('Dotdigitalgroup_Email_Model_Apiconnector_Client');

		//method data
		$meth        = $rc->getMethod( $method );
		//grab the doc block for the method
		$docCommment = $meth->getDocComment();

		//select only the first text
		preg_match( '/([A-Z])+.*\./', $docCommment, $matches );

		if ( isset( $matches[0] ) ) {
			//return only selected text
			return $matches[0];
		}
		//return all doc block
		return $docCommment;
	}
}
