<?php

class Dotdigitalgroup_Email_Model_Email_Queue extends Mage_Core_Model_Email_Queue
{
    /**
     * @return $this|Mage_Core_Model_Email_Queue
     * @throws Zend_Mail_Exception
     */
    public function send()
    {
        $helper = Mage::helper('ddg/transactional');
        // If it's not enabled, just return the parent result.
        if (! $helper->isEnabled()) {
            return parent::send();
        }

        /** @var $collection Mage_Core_Model_Resource_Email_Queue_Collection */
        $collection = Mage::getModel('core/email_queue')->getCollection()
            ->addOnlyForSendingFilter()
            ->setPageSize(self::MESSAGES_LIMIT_PER_CRON_RUN)
            ->setCurPage(1)
            ->load();

        ini_set('SMTP', Mage::getStoreConfig('system/smtp/host'));
        ini_set('smtp_port', Mage::getStoreConfig('system/smtp/port'));

        /** @var $message Mage_Core_Model_Email_Queue */
        foreach ($collection as $message) {
            if ($message->getId()) {
                $parameters = new Varien_Object($message->getMessageParameters());
                if ($parameters->getReturnPathEmail() !== null) {
                    $mailTransport = new Zend_Mail_Transport_Sendmail("-f" . $parameters->getReturnPathEmail());
                    Zend_Mail::setDefaultTransport($mailTransport);
                }
                if ($message->getEntityType() === 'order') {
                    $storeId = $this->getStoreIdFromOrder($message->getEntityId());
                } else {
                    $storeId = Mage::app()->getStore()->getId();
                }

                $transport = $helper->getTransport($storeId);
                $mailer = new Zend_Mail('utf-8');
                foreach ($message->getRecipients() as $recipient) {
                    list($email, $name, $type) = $recipient;
                    switch ($type) {
                        case self::EMAIL_TYPE_BCC:
                            $mailer->addBcc($email, '=?utf-8?B?' . base64_encode($name) . '?=');
                            break;
                        case self::EMAIL_TYPE_TO:
                        case self::EMAIL_TYPE_CC:
                        default:
                            $mailer->addTo($email, '=?utf-8?B?' . base64_encode($name) . '?=');
                            break;
                    }
                }

                if ($parameters->getIsPlain()) {
                    $mailer->setBodyText($message->getMessageBody());
                } else {
                    $mailer->setBodyHTML($message->getMessageBody());
                }

                $mailer->setSubject('=?utf-8?B?' . base64_encode($parameters->getSubject()) . '?=');
                $mailer->setFrom($parameters->getFromEmail(), $parameters->getFromName());
                if ($parameters->getReplyTo() !== null) {
                    $mailer->setReplyTo($parameters->getReplyTo());
                }
                if ($parameters->getReturnTo() !== null) {
                    $mailer->setReturnPath($parameters->getReturnTo());
                }

                try {
                    $mailer->send($transport);
                } catch (Exception $e) {
                    Mage::logException($e);
                }

                unset($mailer);
                $message->setProcessedAt(Varien_Date::formatDate(true));
                $message->save();
            }
        }

        return $this;
    }

    /**
     * Returns Store Id from Order
     * @param $orderId
     * @return mixed
     */
    protected function getStoreIdFromOrder($orderId)
    {
        /** @var  $model Mage_Sales_Model_Order */
        $model = Mage::getModel('sales/order')->load($orderId);
        return $model->getStoreId();
    }
}
