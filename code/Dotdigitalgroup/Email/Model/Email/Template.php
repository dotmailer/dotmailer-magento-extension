<?php

class Dotdigitalgroup_Email_Model_Email_Template extends Mage_Core_Model_Email_Template
{
    /**
     * @codingStandardsIgnoreStart
     * @param array|string $email
     * @param null $name
     * @param array $variables
     * @return bool
     */
    public function send($email, $name = null, array $variables = array())
    {
        $helper = Mage::helper('ddg/transactional');
        // If it's not enabled, just return the parent result.
        if (! $helper->isEnabled()) {
            return parent::send($email, $name, $variables);
        }

        // As per parent class - except addition of before and after send events
        if (!$this->isValidForSend()) {
            Mage::helper('ddg')->log(
                'Email is not valid for sending, this is a core error that often means there\'s a problem with your 
                email templates.'
            );
            Mage::logException(new Exception('This letter cannot be sent.'));
            return false;
        }

        $emails = array_values((array)$email);
        $names = is_array($name) ? $name : (array)$name;
        $names = array_values($names);
        foreach ($emails as $key => $email) {
            if (!isset($names[$key])) {
                $names[$key] = substr($email, 0, strpos($email, '@'));
            }
        }

        $variables['email'] = reset($emails);
        $variables['name'] = reset($names);

        $this->setUseAbsoluteLinks(true);
        $text = $this->getProcessedTemplate($variables, true);
        $subject = $this->getProcessedTemplateSubject($variables);

        $storeId = null;

        // Get the current store Id
        if (isset($variables['store'])) {
            $storeId = $variables['store']->getStoreId();
        }

        ini_set('SMTP', Mage::getStoreConfig('system/smtp/host'));
        ini_set('smtp_port', Mage::getStoreConfig('system/smtp/port'));

        $mail = $this->getMail();

        $setReturnPath = Mage::getStoreConfig(
            self::XML_PATH_SENDING_SET_RETURN_PATH
        );
        switch ($setReturnPath) {
            case 1:
                $returnPathEmail = $this->getSenderEmail();
                break;
            case 2:
                $returnPathEmail = Mage::getStoreConfig(self::XML_PATH_SENDING_RETURN_PATH_EMAIL);
                break;
            default:
                $returnPathEmail = null;
                break;
        }

        if ($this->hasQueue() && $this->getQueue() instanceof Mage_Core_Model_Email_Queue) {
            /** @var $emailQueue Mage_Core_Model_Email_Queue */
            $emailQueue = $this->getQueue();
            $emailQueue->setMessageBody($text);
            $emailQueue->setMessageParameters(array(
                    'subject'           => $subject,
                    'return_path_email' => $returnPathEmail,
                    'is_plain'          => $this->isPlain(),
                    'from_email'        => $this->getSenderEmail(),
                    'from_name'         => $this->getSenderName(),
                    'reply_to'          => $this->getMail()->getReplyTo(),
                    'return_to'         => $this->getMail()->getReturnPath(),
                ))
                ->addRecipients($emails, $names, Mage_Core_Model_Email_Queue::EMAIL_TYPE_TO)
                ->addRecipients($this->_bccEmails, array(), Mage_Core_Model_Email_Queue::EMAIL_TYPE_BCC);
            $emailQueue->addMessageToQueue();

            return true;
        }

        if ($returnPathEmail !== null) {
            $mailTransport = new Zend_Mail_Transport_Sendmail("-f" . $returnPathEmail);
            Zend_Mail::setDefaultTransport($mailTransport);
        }

        foreach ($emails as $key => $email) {
            $mail->addTo($email, '=?utf-8?B?' . base64_encode($names[$key]) . '?=');
        }

        if ($this->isPlain()) {
            $mail->setBodyText($text);
        } else {
            $mail->setBodyHTML($text);
        }

        $mail->setSubject('=?utf-8?B?' . base64_encode($this->getProcessedTemplateSubject($variables)) . '?=');

        //sender name and sender email if the template is from Engagement Cloud
        if ($helper->isDotmailerTemplate($this->getTemplateCode())) {
            $mail->setFrom($this->getTemplateSenderEmail(), $this->getTemplateSenderName());
        } else {
            $mail->setFrom($this->getSenderEmail(), $this->getSenderName());
        }

        try {
            $transport = $helper->getTransport($storeId);

            $mail->send($transport);

            $this->_mail = null;
        } catch (Exception $e) {
            $this->_mail = null;
            Mage::logException($e);

            return false;
        }

        //@codingStandardsIgnoreEnd

        return true;
    }

    /**
     * Compress the template body.
     *
     * @return Mage_Core_Model_Email_Template
     */
    protected function _beforeSave()
    {
        $transactionalHelper = Mage::helper('ddg/transactional');
        if (! $transactionalHelper->isStringCompressed($this->getTemplateText()) &&
            $transactionalHelper->isDotmailerTemplate($this->getTemplateCode())
        ) {
            $this->setTemplateText($transactionalHelper->compresString($this->getTemplateText()));
        }

        return parent::_beforeSave();
    }

    /**
     * @return Mage_Core_Model_Abstract
     */
    protected function _afterLoad()
    {
        //decompress the subject
        $this->setTemplateSubject($this->getTemplateSubject());
        $templateText = $this->getTemplateText();
        $transactionalHelper = Mage::helper('ddg/transactional');
        //decompress the content body
        if ($transactionalHelper->isStringCompressed($templateText)) {
            $this->setTemplateText($transactionalHelper->decompresString($this->getTemplateText()));
        }

        return parent::_afterLoad();
    }
}
