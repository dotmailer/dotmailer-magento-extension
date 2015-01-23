<?php
require_once 'Dotdigitalgroup' . DS . 'Email' . DS . 'controllers' . DS . 'DynamicContentController.php';

class Dotdigitalgroup_Email_InvoiceController extends Dotdigitalgroup_Email_DynamicContentController
{
	/**
	 * New invoice for order.
	 */
	public function newAction()
    {
        $this->loadLayout();
	    //set content template
        $invoiceNew = $this->getLayout()->createBlock('email_connector/order_invoice', 'connector_invoice_new', array(
            'template' => 'connector/invoice/new.phtml'
        ));
        $this->getLayout()->getBlock('content')->append($invoiceNew);
	    //invoice items
        $items = $this->getLayout()->createBlock('email_connector/order_invoice', 'connector_order_items', array(
            'template' => 'connector/order/items.phtml'
        ));
        $this->getLayout()->getBlock('connector_invoice_new')->append($items);
        $this->renderLayout();
        $this->checkContentNotEmpty($this->getLayout()->getOutput());
    }

	/**
	 * New guest invoice.
	 */
	public function newguestAction()
    {
        $this->loadLayout();
        $invoiceGuest = $this->getLayout()->createBlock('email_connector/order_invoice', 'connector_invoiceguest_new', array(
            'template' => 'connector/invoice/newguest.phtml'
        ));

        $this->getLayout()->getBlock('content')->append($invoiceGuest);
        $items = $this->getLayout()->createBlock('email_connector/order_invoice', 'connector_order_items', array(
            'template' => 'connector/order/items.phtml'
        ));
	    //set invoice items
        $this->getLayout()->getBlock('connector_invoiceguest_new')->append($items);
        $this->renderLayout();
        $this->checkContentNotEmpty($this->getLayout()->getOutput());
    }

	/**
	 * Invoice update information.
	 */
	public function updateAction()
    {
        $this->loadLayout();
        $invoice = $this->getLayout()->createBlock('email_connector/order_invoice', 'connector_invoice_update', array(
            'template' => 'connector/invoice/update.phtml'
        ));
	    //set invoice content
        $this->getLayout()->getBlock('content')->append($invoice);
        $this->renderLayout();
        $this->checkContentNotEmpty($this->getLayout()->getOutput());
    }

	/**
	 * Invoice guest.
	 */
	public function updateguestAction()
    {
        $this->loadLayout();
        $invoice = $this->getLayout()->createBlock('email_connector/order_invoice', 'connector_invoice_updateguest', array(
            'template' => 'connector/invoice/updateguest.phtml'
        ));
	    //set invoice content
        $this->getLayout()->getBlock('content')->append($invoice);
        $this->renderLayout();
        $this->checkContentNotEmpty($this->getLayout()->getOutput());
    }
}