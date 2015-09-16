<?php

class Dotdigitalgroup_Email_Block_Recommended_Products extends Dotdigitalgroup_Email_Block_Edc
{
	/**
	 * Slot div name.
	 * @var string
	 */
	public $slot;

    /**
     * get the products to display for table
     */
    public function getLoadedProductCollection()
    {
        $orderModel = Mage::registry('current_order');
		if (! $orderModel) {
			Mage::throwException('no current_order found for EDC');
		}

		//display mode based on the action name
		$mode  = $this->getRequest()->getActionName();
	    //number of product items to be displayed
        $limit      = Mage::helper('ddg/recommended')->getDisplayLimitByMode($mode);
        $orderItems = $orderModel->getAllItems();

        $productsToDisplay = $this->getProductsToDisplay($orderItems, $limit, $mode, 'PRODUCT');
        return $productsToDisplay;
    }

	/**
	 * Nosto products data.
	 * @return object
	 */
	public function getNostoProducts()
	{
		$client = Mage::getModel('ddg_automation/apiconnector_client');
		//slot name, div id
		$slot  = Mage::app()->getRequest()->getParam('slot', false);

		//email recommendation
		$email = Mage::app()->getRequest()->getParam('email', false);

		//no valid data for nosto recommendation
		if (!$slot || ! $email)
			return false;
		else
			$this->slot = $slot;

		//html data from nosto
		$data = $client->getNostoProducts($slot, $email);

		//check for valid response
		if (! isset($data->$email) && !isset($data->$email->$slot))
			return false;
		return $data->$email->$slot;
	}

	/**
	 * Slot name.
	 * Should be called after getNostoProducts.
	 * @return string
	 */
	public function getSlotName()
	{
		return $this->slot;
	}
}