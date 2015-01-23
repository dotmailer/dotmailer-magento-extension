<?php

class Dotdigitalgroup_Email_Block_Adminhtml_Review extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        parent::__construct();

        $this->_blockGroup = 'email_connector';
        $this->_controller = 'adminhtml_review';
        $this->_headerText = Mage::helper('connector')->__('Email Review(s)');

        $this->_removeButton('add');
    }
}
