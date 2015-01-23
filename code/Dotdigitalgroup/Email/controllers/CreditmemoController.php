<?php
require_once 'Dotdigitalgroup' . DS . 'Email' . DS . 'controllers' . DS . 'DynamicContentController.php';

class Dotdigitalgroup_Email_CreditmemoController extends Dotdigitalgroup_Email_DynamicContentController
{

	/**
	 * New creditmemo.
	 */
	public function newAction()
    {
        $this->loadLayout();
        $newOrder = $this->getLayout()->createBlock('email_connector/order_creditmemo', 'connector_creditmemo_new', array(
            'template' => 'connector/creditmemo/new.phtml'
        ));
        $this->getLayout()->getBlock('content')->append($newOrder);
        $items = $this->getLayout()->createBlock('email_connector/order_creditmemo', 'connector_creditmemo_items', array(
            'template' => 'connector/creditmemo/items.phtml'
        ));
        $this->getLayout()->getBlock('connector_creditmemo_new')->append($items);
        $this->renderLayout();
        $this->checkContentNotEmpty($this->getLayout()->getOutput());
    }

	/**
	 * New guest action.
	 */
	public function newguestAction()
    {
        $this->loadLayout();
        $invoice = $this->getLayout()->createBlock('email_connector/order_invoice', 'connector_creditmemo_guest', array(
            'template' => 'connector/creditmemo/newguest.phtml'
        ));
        $this->getLayout()->getBlock('content')->append($invoice);
        $items = $this->getLayout()->createBlock('email_connector/order_creditmemo', 'connector_creditmemo_items', array(
            'template' => 'connector/creditmemo/items.phtml'
        ));
        $this->getLayout()->getBlock('connector_creditmemo_guest')->append($items);
        $this->renderLayout();
        $this->checkContentNotEmpty($this->getLayout()->getOutput());
    }

	/**
	 * update creditmemo.
	 */
	public function updateAction()
    {
        $this->loadLayout();
        $newOrder = $this->getLayout()->createBlock('email_connector/order_creditmemo', 'connector_creditmemo_update', array(
            'template' => 'connector/creditmemo/update.phtml'
        ));
        $this->getLayout()->getBlock('content')->append($newOrder);
        $this->renderLayout();
        $this->checkContentNotEmpty($this->getLayout()->getOutput());
    }

	/**
	 * update guest.
	 */
	public function updateguestAction()
    {
        $this->loadLayout();
        $newOrder = $this->getLayout()->createBlock('email_connector/order_creditmemo', 'connector_creditmemo_update_guest', array(
            'template' => 'connector/creditmemo/updateguest.phtml'
        ));
        $this->getLayout()->getBlock('content')->append($newOrder);
        $this->renderLayout();
        $this->checkContentNotEmpty($this->getLayout()->getOutput());
    }
}