<?php

class Dotdigitalgroup_Email_Block_Adminhtml_Wishlist extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        parent::__construct();

        $this->_blockGroup = 'email_connector';
        $this->_controller = 'adminhtml_wishlist';
        $this->_headerText = Mage::helper('connector')->__('Email Wishlist(s)');

        $this->_removeButton('add');
    }
}
