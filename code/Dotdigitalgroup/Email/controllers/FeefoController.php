<?php
require_once 'Dotdigitalgroup' . DS . 'Email' . DS . 'controllers' . DS
    . 'ResponseController.php';

class Dotdigitalgroup_Email_FeefoController
    extends Dotdigitalgroup_Email_ResponseController
{

    /**
     * @return Mage_Core_Controller_Front_Action|void
     */
    public function preDispatch()
    {
        $helper = Mage::helper('ddg');
        //authenticate
        $this->authenticate();

        $actionName = $this->getRequest()->getActionName();
        switch ($actionName) {
            case 'score':
                if (! $helper->getFeefoLogon()) {
                    $this->sendResponse();

                    return;
                }
                break;
            case 'reviews':
                if (! $helper->getFeefoLogon() or ! Mage::app()->getRequest()
                        ->getParam('quote_id')
                ) {
                    $this->sendResponse();

                    return;
                }
                break;
        }

        parent::preDispatch();
    }

    /**
     * Show customer's score logo.
     */
    public function scoreAction()
    {
        $this->loadLayout();
        $block = $this->getLayout()->getBlock('connector_feefo_service_score');
        $this->checkContentNotEmpty($block->toHtml(), false);
        $this->renderLayout();
    }

    /**
     * Show product reviews.
     */
    public function reviewsAction()
    {
        $this->loadLayout();
        $this->renderLayout();
        $this->checkContentNotEmpty($this->getLayout()->getOutput());
    }
}