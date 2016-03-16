<?php

class Dotdigitalgroup_Email_Customer_AccountController
    extends Mage_Core_Controller_Front_Action
{

    /**
     * Checking if user is logged in. If not logged in then redirect to customer login
     */
    public function preDispatch()
    {
        parent::preDispatch();

        if ( ! Mage::getSingleton('customer/session')->authenticate($this)) {
            $this->setFlag('', 'no-dispatch', true);

            // adding message in customer login page
            Mage::getSingleton('core/session')
                ->addNotice(
                    Mage::helper('ddg')->__(
                        'Please sign in or create a new account'
                    )
                );
        }
    }

    public function statsAction()
    {
        $this->loadLayout();
        $this->getLayout()->getBlock('head')->setTitle(
            $this->__('Email Activity')
        );
        $this->renderLayout();
    }
}