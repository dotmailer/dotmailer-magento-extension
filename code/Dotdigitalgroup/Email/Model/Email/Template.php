<?php

class Dotdigitalgroup_Email_Model_Email_Template
    extends Mage_Core_Model_Email_Template
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
        if (!$helper->isEnabled()) {
            return parent::send($email, $name, $variables);
        }

        // As per parent class - except addition of before and after send events
        if (!$this->isValidForSend()) {
            Mage::log(
                'Email is not valid for sending, this is a core error that often means there\'s a problem with your 
                email templates.'
            );
            Mage::logException(new Exception('This letter cannot be sent.'));
            return false;
        }

        $emails = array_values((array)$email);
        $names  = is_array($name) ? $name : (array)$name;
        $names  = array_values($names);
        foreach ($emails as $key => $email) {
            if (!isset($names[$key])) {
                $names[$key] = substr($email, 0, strpos($email, '@'));
            }
        }

        $variables['email'] = reset($emails);
        $variables['name']  = reset($names);

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
                $returnPathEmail = Mage::getStoreConfig(
                    self::XML_PATH_SENDING_RETURN_PATH_EMAIL
                );
                break;
            default:
                $returnPathEmail = null;
                break;
        }

        if ($returnPathEmail !== null) {
            $mailTransport = new Zend_Mail_Transport_Sendmail(
                "-f" . $returnPathEmail
            );
            Zend_Mail::setDefaultTransport($mailTransport);
        }

        foreach ($emails as $key => $email) {
            $mail->addTo(
                $email, '=?utf-8?B?' . base64_encode($names[$key]) . '?='
            );
        }

        $this->setUseAbsoluteLinks(true);
        $text = $this->getProcessedTemplate($variables, true);

        if ($this->isPlain()) {
            $mail->setBodyText($text);
        } else {
            $mail->setBodyHTML($text);
        }

        $mail->setSubject(
            '=?utf-8?B?' . base64_encode(
                $this->getProcessedTemplateSubject($variables)
            ) . '?='
        );
        $mail->setFrom($this->getSenderEmail(), $this->getSenderName());

        try {
            $transport = $helper->getTransport();

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

}