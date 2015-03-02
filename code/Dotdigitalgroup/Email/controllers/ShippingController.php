<?php
require_once 'Dotdigitalgroup' . DS . 'Email' . DS . 'controllers' . DS . 'DynamicContentController.php';

class Dotdigitalgroup_Email_ShippingController extends Dotdigitalgroup_Email_DynamicContentController
{
	/**
	 * New shipping for this order.
	 */
	public function newAction()
    {
        $this->loadLayout();
	    //set content template
        $newOrder = $this->getLayout()->createBlock('ddg_automation/order_shipping', 'connector_shipping_new', array(
            'template' => 'connector/shipping/new.phtml'
        ));
        $this->getLayout()->getBlock('content')->append($newOrder);
	    //set content items
        $items = $this->getLayout()->createBlock('ddg_automation/order', 'connector_shipping_items', array(
            'template' => 'connector/order/items.phtml'
        ));
        $this->getLayout()->getBlock('connector_shipping_new')->append($items);
	    //rewrite the items to dislpay the shipped ones
        $items = $this->getLayout()->createBlock('ddg_automation/order_shipping', 'connector_shipping_track', array(
            'template' => 'email/order/shipment/track.phtml'
        ));
        $this->getLayout()->getBlock('connector_shipping_new')->append($items);
        $this->renderLayout();
        $this->checkContentNotEmpty($this->getLayout()->getOutput());
    }

	/**
	 * New shipping for guest.
	 */
	public function newguestAction()
    {
        $this->loadLayout();
	    //set content template
        $newOrder = $this->getLayout()->createBlock('ddg_automation/order_shipping', 'connector_shipping_newguest', array(
            'template' => 'connector/shipping/newguest.phtml'
        ));
        $this->getLayout()->getBlock('content')->append($newOrder);
	    //set content items
        $items = $this->getLayout()->createBlock('ddg_automation/order', 'connector_shipping_items', array(
            'template' => 'connector/order/items.phtml'
        ));
	    //new guest shipping items
        $this->getLayout()->getBlock('connector_shipping_newguest')->append($items);
	    //rewrite the items to dislpay the shipped ones
        $items = $this->getLayout()->createBlock('ddg_automation/order_shipping', 'connector_shipping_track', array(
            'template' => 'email/order/shipment/track.phtml'
        ));
	    //items that was shipped
        $this->getLayout()->getBlock('connector_shipping_newguest')->append($items);
        $this->renderLayout();
        $this->checkContentNotEmpty($this->getLayout()->getOutput());
    }

	/**
	 * Shipping update for this order.
	 */
	public function updateAction()
    {
        $this->loadLayout();
	    //set the content template
        $shippingUpdate = $this->getLayout()->createBlock('ddg_automation/order_shipping', 'connector_shipping_update', array(
            'template' => 'connector/shipping/update.phtml'
        ));
	    //shipping update content
        $this->getLayout()->getBlock('content')->append($shippingUpdate);
        $this->renderLayout();
        $this->checkContentNotEmpty($shippingUpdate->toHtml());
    }

	/**
	 * Shipping update for guests.
	 */
	public function updateguestAction()
    {
        $this->loadLayout();
        $shippingGuest = $this->getLayout()->createBlock('ddg_automation/order_shipping', 'connector_shipping_updateguest', array(
            'template' => 'connector/shipping/updateguest.phtml'
        ));
	    //set shipping content
        $this->getLayout()->getBlock('content')->append($shippingGuest);
        $this->renderLayout();
        $this->checkContentNotEmpty($this->getLayout()->getOutput());
    }
}