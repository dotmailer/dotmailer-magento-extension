<?php

class Dotdigitalgroup_Email_Model_Email extends Mage_Core_Model_Email
{

    public function send() {


        if ( ! Mage::helper('ddg/transactional')->isEnabled() ) {
            return parent::send();
        }

        if (Mage::getStoreConfigFlag('system/smtp/disable')) {
            return $this;
        }

        $mail = new Zend_Mail();

        if (strtolower($this->getType()) == 'html') {
            $mail->setBodyHtml($this->getBody());
        } else {
            $mail->setBodyText($this->getBody());
        }

        $mail->setFrom($this->getFromEmail(), $this->getFromName())
            ->addTo($this->getToEmail(), $this->getToName())
            ->setSubject($this->getSubject());

        $transport = Mage::helper('ddg/transactional')->getTransport();

        $mail->send($transport);


        return $this;
    }
}