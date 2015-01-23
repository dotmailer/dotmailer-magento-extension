<?php
require_once 'Dotdigitalgroup' . DS . 'Email' . DS . 'controllers' . DS . 'ResponseController.php';


class Dotdigitalgroup_Email_ReportController extends Dotdigitalgroup_Email_ResponseController
{
	/**
	 * @return Mage_Core_Controller_Front_Action|void
	 */
	public function preDispatch()
    {
	    //authenticate
        Mage::helper('connector')->auth($this->getRequest()->getParam('code'));
        parent::preDispatch();
    }

	/**
	 * Bestsellers report.
	 */
	public function bestsellersAction()
    {
        $this->loadLayout();
	    //set the content template
        $products = $this->getLayout()->createBlock('email_connector/recommended_bestsellers', 'connector_customer', array(
            'template' => 'connector/product/list.phtml'
        ));
        $this->getLayout()->getBlock('content')->append($products);
        $this->renderLayout();
        $this->checkContentNotEmpty($this->getLayout()->getOutput());
    }

	/**
	 * Most viewed report.
	 */
	public function mostviewedAction()
    {
        $this->loadLayout();
	    //set the content template
        $products = $this->getLayout()->createBlock('email_connector/recommended_mostviewed', 'connector_customer', array(
            'template' => 'connector/product/list.phtml'
        ));
        $this->getLayout()->getBlock('content')->append($products);
        $this->renderLayout();
        $this->checkContentNotEmpty($this->getLayout()->getOutput());
    }

	/**
	 * Recently viewed products for customer.
	 */
	public function recentlyviewedAction()
    {
	    //customer id param
        $customerId = $this->getRequest()->getParam('customer_id');
	    //no customer was found
        if (! $customerId) {
            throw new Exception('Recentlyviewed : no customer id : ' . $customerId);
        }
        $this->loadLayout();
	    //set content template
        $products = $this->getLayout()->createBlock('email_connector/recommended_recentlyviewed', 'connector_customer', array(
            'template' => 'connector/product/list.phtml'
        ));
        $this->getLayout()->getBlock('content')->append($products);
        $this->renderLayout();
        $this->checkContentNotEmpty($this->getLayout()->getOutput());
    }
}