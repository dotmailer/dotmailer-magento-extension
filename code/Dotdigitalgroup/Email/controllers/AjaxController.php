<?php

class Dotdigitalgroup_Email_AjaxController
    extends Mage_Core_Controller_Front_Action
{

    /**
     * @return null
     */
    public function emailcaptureAction()
    {
        if ($this->getRequest()->getParam('email') &&
            Mage::getSingleton(
                'checkout/session'
            )->getQuote()
        ) {
            $email = $this->getRequest()->getParam('email');
            $email = filter_var($email, FILTER_SANITIZE_EMAIL);
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                Mage::helper('ddg')->log(
                    'ajax emailCapture fail for given email. Failed by regex'
                );

                return null;
            }

            $quote = Mage::getSingleton('checkout/session')->getQuote();
            if ($quote->hasItems()) {
                try {
                    $quote->setCustomerEmail($email)->save();
                    Mage::helper('ddg')->log(
                        'ajax emailCapture email: ' . $email
                    );
                } catch (Exception $e) {
                    Mage::logException($e);
                    Mage::helper('ddg')->log(
                        'ajax emailCapture fail for email: ' . $email
                    );
                }
            }
        }
    }
}