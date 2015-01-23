<?php

class Dotdigitalgroup_Email_Block_Adminhtml_Order extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        parent::__construct();

        $this->_blockGroup = 'email_connector';
        $this->_controller = 'adminhtml_order';
        $this->_headerText = Mage::helper('connector')->__('Email Order(s)');

        $this->_removeButton('add');
    }
}
