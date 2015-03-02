<?php
require_once 'Dotdigitalgroup' . DS . 'Email' . DS . 'controllers' . DS . 'ResponseController.php';

class Dotdigitalgroup_Email_FeefoController extends Dotdigitalgroup_Email_ResponseController
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
                if(!$helper->getFeefoLogon()){
                    $this->sendResponse();
                    die;
                }
                break;
            case 'reviews':
                if(!$helper->getFeefoLogon() or !Mage::app()->getRequest()->getParam('quote_id')){
                    $this->sendResponse();
                    die;
                }
                break;
        }

        parent::preDispatch();
    }

    /**
     * show customer's score logo
     */
    public function scoreAction()
    {
        $this->loadLayout();

        $block = $this->getLayout()->createBlock('ddg_automation/feefo', 'connector_feefo_service_score', array(
            'template' => 'connector/feefo/score.phtml'
        ));
        $this->getLayout()->getBlock('content')->append($block);
        $this->checkContentNotEmpty($block->toHtml(), false);
        $this->renderLayout();
    }

    /**
     * show product reviews
     */
    public function reviewsAction()
    {
        $this->loadLayout();

        $block = $this->getLayout()->createBlock('ddg_automation/feefo', 'connector_feefo_product_reviews', array(
            'template' => 'connector/feefo/reviews.phtml'
        ));
        $this->getLayout()->getBlock('content')->append($block);
        $this->renderLayout();
        $this->checkContentNotEmpty($this->getLayout()->getOutput());
    }
}