<?php

class Dotdigitalgroup_Email_Block_Adminhtml_Config extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    /**
	 * Set the template.
	 */
    public function __construct()
    {
        $this->_controller         = 'adminhtml_config';
        $this->_blockGroup         = 'ddg_automation';
        parent::__construct();
        $this->_headerText         = Mage::helper('ddg')->__('Config');
        $this->_removeButton('add');

        $this->setTemplate('connector/grid.phtml');
    }
}