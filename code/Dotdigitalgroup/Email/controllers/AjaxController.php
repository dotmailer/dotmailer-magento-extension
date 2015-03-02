<?php

class Dotdigitalgroup_Email_AjaxController extends Mage_Core_Controller_Front_Action
{
    public function emailcaptureAction()
    {
        if($this->getRequest()->getParam('email') && Mage::getSingleton('checkout/session')->getQuote()){
            $email = $this->getRequest()->getParam('email');
            $quote = Mage::getSingleton('checkout/session')->getQuote();
            if($quote->hasItems()){
                try {
                    $quote->setCustomerEmail($email)->save();
                    Mage::helper('ddg')->log('ajax emailCapture email: '. $email);
                }catch(Exception $e){
                    Mage::logException($e);
                    Mage::helper('ddg')->log('ajax emailCapture fail for email: '. $email);
                }
            }
        }
    }
}