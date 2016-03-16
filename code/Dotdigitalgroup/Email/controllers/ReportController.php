<?php
require_once 'Dotdigitalgroup' . DS . 'Email' . DS . 'controllers' . DS
    . 'ResponseController.php';


class Dotdigitalgroup_Email_ReportController
    extends Dotdigitalgroup_Email_ResponseController
{

    /**
     * @return Mage_Core_Controller_Front_Action|void
     */
    public function preDispatch()
    {
        //authenticate
        $this->authenticate();
        parent::preDispatch();
    }

    /**
     * Bestsellers report.
     */
    public function bestsellersAction()
    {
        $this->loadLayout();
        $this->renderLayout();
        $this->checkContentNotEmpty($this->getLayout()->getOutput());
    }

    /**
     * Most viewed report.
     */
    public function mostviewedAction()
    {
        $this->loadLayout();
        $this->renderLayout();
        $this->checkContentNotEmpty($this->getLayout()->getOutput());
    }

    /**
     * Recently viewed products for customer.
     */
    public function recentlyviewedAction()
    {
        //customer id param
        $customerId = $this->getRequest()->getParam('customer_id', false);
        //no customer was found
        if ( ! $customerId) {

            Mage::helper('ddg')->log(
                'Recentlyviewed : no customer id : ' . $customerId
            );
            $this->sendResponse();
            Mage::throwException('Recentlyviewed, customer not set');
        }
        $this->loadLayout();
        $this->renderLayout();
        $this->checkContentNotEmpty($this->getLayout()->getOutput());
    }
}