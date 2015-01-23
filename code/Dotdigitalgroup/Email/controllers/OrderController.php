<?php
require_once 'Dotdigitalgroup' . DS . 'Email' . DS . 'controllers' . DS . 'DynamicContentController.php';

class Dotdigitalgroup_Email_OrderController extends Dotdigitalgroup_Email_DynamicContentController
{
	/**
	 * Display new order content.
	 */
	public function newAction()
    {
        $this->loadLayout();
	    //set content template
        $newOrder = $this->getLayout()->createBlock('email_connector/order', 'connector_order', array(
            'template' => 'connector/order/new.phtml'
        ));
        $this->getLayout()->getBlock('content')->append($newOrder);
	    //set the items for this order
        $items = $this->getLayout()->createBlock('email_connector/order', 'connector_order_items', array(
            'template' => 'connector/order/items.phtml'
        ));
        $this->getLayout()->getBlock('connector_order')->append($items);
        $this->renderLayout();
        $this->checkContentNotEmpty($this->getLayout()->getOutput());
    }

	/**
	 * New order for guest.
	 */
	public function newguestAction()
    {
        $this->loadLayout();
	    //set content template
        $newGuestOrder = $this->getLayout()->createBlock('email_connector/order', 'connector_order_guest', array(
            'template' => 'connector/order/newguest.phtml'
        ));
        $this->getLayout()->getBlock('content')->append($newGuestOrder);
	    //set the items for this order
        $items = $this->getLayout()->createBlock('email_connector/order', 'connector_order_items', array(
            'template' => 'connector/order/items.phtml'
        ));
        $this->getLayout()->getBlock('connector_order_guest')->append($items);
        $this->renderLayout();
        $this->checkContentNotEmpty($this->getLayout()->getOutput());
    }

	/**
	 * Show order update information.
	 */
	public function updateAction()
    {
        $this->loadLayout();
	    //set content template
        $newOrder = $this->getLayout()->createBlock('email_connector/order', 'connector_order_update', array(
            'template' => 'connector/order/update.phtml'
        ));
        $this->getLayout()->getBlock('content')->append($newOrder);
        $this->renderLayout();
        $this->checkContentNotEmpty($this->getLayout()->getOutput());
    }

	/**
	 * Show order update for guest.
	 */
	public function updateguestAction()
    {
        $this->loadLayout();
	    //set the content template
        $newOrder = $this->getLayout()->createBlock('email_connector/order', 'connector_order_update_guest', array(
            'template' => 'connector/order/updateguest.phtml'
        ));
        $this->getLayout()->getBlock('content')->append($newOrder);
        $this->renderLayout();
        $this->checkContentNotEmpty($this->getLayout()->getOutput());
    }
}