<?php

class Dotdigitalgroup_Email_Block_Adminhtml_Campaign extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    /**
	 * Set the template.
	 */
    public function __construct()
    {
        $this->_controller         = 'adminhtml_campaign';
        $this->_blockGroup         = 'email_connector';
        parent::__construct();
        $this->_headerText         = Mage::helper('connector')->__('Campaigns');
        $this->_removeButton('add');

        $this->setTemplate('connector/grid.phtml');
    }
}