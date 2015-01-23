<?php

class Dotdigitalgroup_Email_Model_Email_Template extends Mage_Core_Model_Email_Template
{
    /**
     * Registered sales emails that can be mapped as transactional emails.
     *
     * @var array
     */
    private $_registered = array(
        'sales_email_order_template'                    => 'New Order',
        'sales_email_order_guest_template'              => 'New Order Guest',
        'sales_email_order_comment_template'            => 'Order Update',
        'sales_email_order_comment_guest_template'      => 'Order Update for Guest',
        'sales_email_invoice_template'                  => 'New Invoice',
        'sales_email_invoice_guest_template'            => 'New Invoice for Guest',
        'sales_email_invoice_comment_template'          => 'Invoice Update',
        'sales_email_invoice_comment_guest_template'    => 'Invoice Update for Guest',
        'sales_email_creditmemo_template'               => 'New Credit Memo',
        'sales_email_creditmemo_guest_template'         => 'New Credit Memo for Guest',
        'sales_email_creditmemo_comment_template'       => 'Credit Memo Update',
        'sales_email_creditmemo_comment_guest_template' => 'Credit Memo Update for Guest',
        'sales_email_shipment_template'                 => 'New Shipment',
        'sales_email_shipment_guest_template'           => 'New Shipment for Guest',
        'sales_email_shipment_comment_template'         => 'Shipment Update',
        'sales_email_shipment_comment_guest_template'   => 'Shipment Update for Guest',
    );
    /**
     * customer emails that can be mapped as transactional emails.
     *
     * @var array
     */
    private $_registeredCustomer = array(
        'customer_create_account_email_template'         => 'New Customer Account'
    );

    /**
     * @var string
     */
    private $_templateId;

    /**
     * @var int
     */
    private $_storeId;

    /**
     * Send transactional email to recipient
     *
     * @see Mage_Core_Model_Email_Template::sendTransactional()
     * @param   string $templateId
     * @param   string|array $sender sneder information, can be declared as part of config path
     * @param   string $email recipient email
     * @param   string $name recipient name
     * @param   array $vars varianles which can be used in template
     * @param   int|null $storeId
     * @return  Mage_Core_Model_Email_Template
     */
    public function sendTransactional($templateId, $sender, $email, $name, $vars=array(), $storeId=null)
    {
        $this->_templateId = $templateId;
	    $sendType = Mage::helper('connector/transactional')->getMapping($templateId, Dotdigitalgroup_Email_Helper_Transactional::MAP_COLUMN_KEY_SENDTYPE, $storeId);
	    $transEnabled = Mage::getStoreConfig(Dotdigitalgroup_Email_Helper_Transactional::XML_PATH_TRANSACTIONAL_API_ENABLED, $storeId);
	    $campaignId = Mage::helper('connector/transactional')->getMapping($templateId,Dotdigitalgroup_Email_Helper_Transactional::MAP_COLUMN_KEY_DATAFIELD, $storeId);


	    //design and send. campaign id is needed for this option
        if ($sendType == 2 && $transEnabled && $campaignId) {
            if(is_array($email)) {
                foreach($email as $one) {
                    $this->setSentSuccess($this->designAndSend($templateId, $vars, $campaignId, $one, $storeId));
                }
            } else {
                $this->setSentSuccess($this->designAndSend($templateId, $vars, $campaignId, $email, $storeId));
            }
            return $this;
        }

        //send via connector
        if($sendType == 1 && $transEnabled)
            return $this->sendTransactionalForOptionViaConnector($sender, $email, $name, $vars, $storeId, $sendType);

        // If Template ID is 'nosend', then simply return false
        if ($templateId == 'nosend') {
            return false;
        }

        //templates not mapped or mapped "Use System Default"
        return parent::sendTransactional($templateId, $sender, $email, $name, $vars, $storeId);
    }

    /**
     * send transactional for option "Send via connector"
     *
     * @param $templateId
     * @param $sender
     * @param $email
     * @param $name
     * @param array $vars
     * @param null $storeId
     * @param $sendType
     * @return $this
     * @throws Mage_Core_Exception
     */
    public function sendTransactionalForOptionViaConnector($sender, $email, $name, $vars = array(), $storeId = null, $sendType)
    {
        $templateId = $this->_templateId;
        $this->setSentSuccess(false);
        if (($storeId === null) && $this->getDesignConfig()->getStore()) {
            $storeId = $this->getDesignConfig()->getStore();
        }

        if (is_numeric($templateId)) {
            $this->load($templateId);
        } else {
            $localeCode = Mage::getStoreConfig('general/locale/code', $storeId);
            $this->loadDefault($templateId, $localeCode);
        }

        if (!$this->getId()) {
            throw Mage::exception('Mage_Core', Mage::helper('core')->__('Invalid transactional email code: %s', $templateId));
        }

        if (!is_array($sender)) {
            $this->setSenderName(Mage::getStoreConfig('trans_email/ident_' . $sender . '/name', $storeId));
            $this->setSenderEmail(Mage::getStoreConfig('trans_email/ident_' . $sender . '/email', $storeId));
        } else {
            $this->setSenderName($sender['name']);
            $this->setSenderEmail($sender['email']);
        }

        if (!isset($vars['store'])) {
            $vars['store'] = Mage::app()->getStore($storeId);
        }
        $this->setSentSuccess($this->send($email, $name, $vars, $sendType));
        return $this;
    }

    /**
     * Send mail to recipient
     *
     * @see Mage_Core_Model_Email_Template::send()
     * @param   array|string       $email        E-mail(s)
     * @param   array|string|null  $name         receiver name(s)
     * @param   array              $variables    template variables
     * @param   int                $sendType
     * @return  boolean
     **/
    public function send($email, $name = null, array $variables = array(), $sendType = null)
    {
        //not mapped templates or mapped as "Use system default"
        if($sendType == null)
            return parent::send($email, $name, $variables);

        $transEnabled = Mage::getStoreConfig(Dotdigitalgroup_Email_Helper_Transactional::XML_PATH_TRANSACTIONAL_API_ENABLED);
        //mapped option "Send via connector"
        if($sendType == 1 && $transEnabled)
            return $this->sendViaConnector($email, $name, $variables);

    }

    /**
     * send via connector
     *
     * @param $email
     * @param $name
     * @param $variables
     * @return bool
     * @throws Exception
     */
    public function sendViaConnector($email, $name, $variables)
    {
        $templateId = $this->_templateId;
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

        $store = $variables['store'];
        $body = $this->getProcessedTemplate($variables, true);
        $subject = $this->getProcessedTemplateSubject($variables);
        $websiteId = $store->getWebsiteId();

        $helper = Mage::helper('connector/transactional');
        $templateName = $helper->getTemplateLabelById($this->getId());

        if($helper->getUnsubscribeLink($websiteId))
            $body .= '<br><br>' . $helper->__('Want to unsubscribe or change your details') . '<a href="http://$UNSUB$">' . $helper->__('Unsubscribe from this newsletter') . '</a>';

        $fromAddress = Mage::helper('connector/transactional')->getMapping($templateId,Dotdigitalgroup_Email_Helper_Transactional::MAP_COLUMN_KEY_FROM_ADDRESS, $store->getId());
        $attachmentId = Mage::helper('connector/transactional')->getMapping($templateId,Dotdigitalgroup_Email_Helper_Transactional::MAP_COLUMN_KEY_ATTACHMENT_ID, $store->getId());

        foreach($emails as $email){
            try{
                $now = Mage::getSingleton('core/date')->gmtDate();
                $emailCreate = Mage::getModel('email_connector/campaign');
                $emailCreate
                    ->setEmail($email)
                    ->setFromName($this->getSenderName())
                    ->setFromAddress($fromAddress)
                    ->setWebsiteId($websiteId)
                    ->setEventName($templateName)
                    ->setSubject($subject)
                    ->setHtmlContent($body)
                    ->setPlainTextContent($helper->__('Want to unsubscribe or change your details?') . 'http://$UNSUB$')
                    ->setAttachmentId($attachmentId)
                    ->setCreatedAt($now)
                    ->setType(2)
                    ->save();
            }catch (Exception $e) {
                Mage::logException($e);
            }
        }
        return true;
    }

    /**
     * design and send
     *
     * @param $templateId
     * @param $templateParams
     * @param $campaignId
     * @param $email
     * @param $storeId
     * @return bool
     */
    public function designAndSend($templateId, $templateParams, $campaignId, $email, $storeId)
    {
        if (array_key_exists($templateId, $this->_registered)) {
            $this->_registerOrderCampaign($templateId, $templateParams, $campaignId);
            return true;
        }
        if (array_key_exists($templateId, $this->_registeredCustomer)) {
            $this->_registerCustomer($templateId, $templateParams, $campaignId);
            return true;
        }

        $this->_registerOtherDesignAndSendCampaign($templateId, $campaignId, $email, $storeId);
        return true;
    }

    /**
     * register sales order campaign.
     *
     * @param $templateId
     * @param $data
     * @param $campaignId
     */
    protected function _registerOrderCampaign($templateId, $data, $campaignId)
    {
        $order = $data['order'];
        $storeId = $order->getStoreId();
	    $websiteId = Mage::getModel('core/store')->load($storeId)->getWebsiteId();

        Mage::helper('connector')->log('-- Sales Order :'  . $campaignId);
        try{
            $now = Mage::getSingleton('core/date')->gmtDate();
            //save email for sending
            $emailCampaign = Mage::getModel('email_connector/campaign');
            $emailCampaign->setOrderIncrementId($order->getRealOrderId())
                ->setQuoteId($order->getQuoteId())
                ->setEmail($order->getCustomerEmail())
                ->setCustomerId($order->getCustomerId())
                ->setStoreId($storeId)
                ->setCampaignId($campaignId)
                ->setEventName($this->_registered[$templateId])
	            ->setWebsiteId($websiteId)
                ->setCreatedAt($now)
            ;
            $emailCampaign->save();
        }catch (Exception $e){
            Mage::logException($e);
        }
    }

    /**
     * register customer campaign.
     *
     * @param $templateId
     * @param $data
     * @param $campaignId
     */
    protected function _registerCustomer($templateId, $data, $campaignId)
    {
        $customer = $data['customer'];
        Mage::helper('connector')->log('-- Customer campaign: '  . $campaignId);
        try{
            $now = Mage::getSingleton('core/date')->gmtDate();
            //save email for sending
            $emailCampaign = Mage::getModel('email_connector/campaign');
            $emailCampaign->setEmail($customer->getEmail())
                ->setCustomerId($customer->getId())
                ->setStoreId($customer->getStoreId())
                ->setCampaignId($campaignId)
                ->setEventName($this->_registeredCustomer[$templateId])
                ->setCreatedAt($now);
            $emailCampaign->save();
        }catch (Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * design and send option. all campaign which are not in array _registered or array _registeredCustomer
     *
     * @param $templateId
     * @param $campaignId
     * @param $email
     * @param $storeId
     */
    protected function _registerOtherDesignAndSendCampaign($templateId, $campaignId, $email, $storeId)
    {
        $helper = Mage::helper('connector/transactional');
        Mage::helper('connector')->log('-- Other campaign: '  . $campaignId);
		$websiteId = Mage::getModel('core/store')->load($storeId)->getWebsiteId();

        try{
            $now = Mage::getSingleton('core/date')->gmtDate();
            if($email){
                //save email for sending
                $emailCampaign = Mage::getModel('email_connector/campaign');
                $emailCampaign
                    ->setEmail($email)
                    ->setStoreId($storeId)
                    ->setCampaignId($campaignId)
	                ->setWebsiteId($websiteId)
	                ->setEventName($helper->getTemplateLabelById($templateId))
                    ->setCreatedAt($now);
                $emailCampaign->save();
            }
        }catch (Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * find template id in array _registered
     *
     * @param $templateId
     * @return bool
     */
    public function getSalesEvent($templateId)
    {
        if (array_key_exists($templateId, $this->_registered))
            return true;

        return false;
    }

}