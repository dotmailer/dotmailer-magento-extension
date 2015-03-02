<?php

class Dotdigitalgroup_Email_Model_Sales_Observer
{

    /**
     * Register the order status.
     * @param $observer
     * @return $this
     */
    public function handleSalesOrderSaveBefore($observer)
    {
        $order = $observer->getEvent()->getOrder();
        // the reloaded status
        $reloaded = Mage::getModel('sales/order')->load($order->getId());
	    if (! Mage::registry('sales_order_status_before'))
            Mage::register('sales_order_status_before', $reloaded->getStatus());
        return $this;
    }
    /**
     * save/reset the order as transactional data.
     *
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function handleSalesOrderSaveAfter(Varien_Event_Observer $observer)
    {
        try{
	        $order = $observer->getEvent()->getOrder();
            $status  = $order->getStatus();
            $storeId = $order->getStoreId();
	        // start app emulation
	        $appEmulation = Mage::getSingleton('core/app_emulation');
	        $initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($storeId);
            $emailOrder = Mage::getModel('ddg_automation/order')->loadByOrderId($order->getEntityId(), $order->getQuoteId());
            //reimport email order
            $emailOrder->setUpdatedAt($order->getUpdatedAt())
                ->setStoreId($storeId)
                ->setOrderStatus($status);
            if($emailOrder->getEmailImported() != Dotdigitalgroup_Email_Model_Contact::EMAIL_CONTACT_IMPORTED) {
                $emailOrder->setEmailImported(null);
            }

            // check for order status change
            $statusBefore = Mage::registry('sales_order_status_before');
            if ( $status!= $statusBefore) {
                //If order status has changed and order is already imported then set imported to null
                if($emailOrder->getEmailImported() == Dotdigitalgroup_Email_Model_Contact::EMAIL_CONTACT_IMPORTED) {
                    $emailOrder->setEmailImported(Dotdigitalgroup_Email_Model_Contact::EMAIL_CONTACT_NOT_IMPORTED);
                }
                $smsCampaign = Mage::getModel('ddg_automation/sms_campaign', $order);
                $smsCampaign->setStatus($status);
                $smsCampaign->sendSms();
	            // set back the current store
	            $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);
            }
            $emailOrder->save();

            //admin oder when editing the first one is canceled
            Mage::unregister('sales_order_status_before');
        }catch(Exception $e){
            Mage::logException($e);
        }
        return $this;
    }


	/**
	 * Create new order event.
	 * @param Varien_Event_Observer $observer
	 *
	 * @return $this
	 * @throws Mage_Core_Exception
	 */
	public function handleSalesOrderPlaceAfter(Varien_Event_Observer $observer)
    {
        /** @var $order Mage_Sales_Model_Order */
        $order = $observer->getEvent()->getOrder();
        $website = Mage::app()->getWebsite($order->getWebsiteId());
        $websiteName = $website->getName();
        $storeName = Mage::app()->getStore($order->getStoreId())->getName();
        $data = array();

        //if api is not enabled
        if (!$website->getConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_API_ENABLED))
            return $this;

        //data fields
        if($last_order_id = $website->getConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOMER_LAST_ORDER_ID)){
            $data[] = array(
                'Key' => $last_order_id,
                'Value' => $order->getId()
            );
        }
        if($order_increment_id = $website->getConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOMER_LAST_ORDER_INCREMENT_ID)){
            $data[] = array(
                'Key' => $order_increment_id,
                'Value' => $order->getIncrementId()
            );
        }
        if($store_name = $website->getConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOMER_STORE_NAME)){
            $data[] = array(
                'Key' => $store_name,
                'Value' => $storeName
            );
        }
        if($website_name = $website->getConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOMER_WEBSITE_NAME)){
            $data[] = array(
                'Key' => $website_name,
                'Value' => $websiteName
            );
        }
        if($last_order_date = $website->getConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOMER_LAST_ORDER_DATE)){
            $data[] = array(
                'Key' => $last_order_date,
                'Value' => $order->getCreatedAt()
            );
        }
        if(($customer_id = $website->getConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOMER_ID)) && $order->getCustomerId()){
            $data[] = array(
                'Key' => $customer_id,
                'Value' => $order->getCustomerId()
            );
        }

        if(!empty($data)){
            //update data fields
            $client = Mage::helper('ddg')->getWebsiteApiClient($website);
            $client->updateContactDatafieldsByEmail($order->getCustomerEmail(), $data);
        }

        //automation enrolment for order
        if($order->getCustomerIsGuest()){
            //send guest to automation mapped
            $automationType = 'XML_PATH_CONNECTOR_AUTOMATION_STUDIO_GUEST_ORDER';
            $email      = $order->getCustomerEmail();
            $websiteId  = $order->getWebsiteId();
            $this->_postCustomerToAutomation($email, $websiteId, $automationType);
        }else{
            //send customer to automation mapped
            $automationType = 'XML_PATH_CONNECTOR_AUTOMATION_STUDIO_ORDER';
            $email      = $order->getCustomerEmail();
            $websiteId  = $order->getWebsiteId();
            $this->_postCustomerToAutomation($email, $websiteId, $automationType);
        }
        return $this;
    }

	/**
	 * Sales order refund event.
	 *
	 * @param Varien_Event_Observer $observer
	 *
	 * @return $this
	 */
	public function handleSalesOrderRefund(Varien_Event_Observer $observer)
    {
        $creditmemo = $observer->getEvent()->getCreditmemo();
        $storeId = $creditmemo->getStoreId();
        $order   = $creditmemo->getOrder();
        $orderId = $order->getEntityId();
        $quoteId = $order->getQuoteId();

        try{
            /**
             * Reimport transactional data.
             */
            $emailOrder = Mage::getModel('ddg_automation/order')->loadByOrderId($orderId, $quoteId, $storeId);
            if (!$emailOrder->getId()) {
                Mage::helper('ddg')->log('ERROR Creditmemmo Order not found :' . $orderId . ', quote id : ' . $quoteId . ', store id ' . $storeId);
                return $this;
            }
            $emailOrder->setEmailImported(Dotdigitalgroup_Email_Model_Contact::EMAIL_CONTACT_NOT_IMPORTED)->save();
        }catch (Exception $e){
            Mage::logException($e);
        }

        return $this;
    }

	/**
	 * Sales cancel order event, remove transactional data.
	 *
	 * @param Varien_Event_Observer $observer
	 *
	 * @return $this
	 */
	public function hangleSalesOrderCancel(Varien_Event_Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $storeId = $order->getStoreId();
        $websiteId = Mage::app()->getStore($storeId)->getWebsiteId();
        $customerEmail = $order->getCustomerEmail();
        $helper = Mage::helper('ddg');
        if ($helper->isEnabled($websiteId)) {
            $client = Mage::getModel('ddg_automation/apiconnector_client');
            $client->setApiUsername($helper->getApiUsername($websiteId));
            $client->setApiPassword($helper->getApiPassword($websiteId));
            // delete the order transactional data
            $client->deleteContactTransactionalData($customerEmail, 'Orders');
        }

        return $this;
    }

    /**
     * enrol single contact to automation
     *
     * @param $email
     * @param $websiteId
     * @param $automationType
     */
    private function _postCustomerToAutomation( $email, $websiteId, $automationType) {
        /**
         * Automation Programme
         */
        $path = constant('Dotdigitalgroup_Email_Helper_Config::' . $automationType);
        $automationCampaignId = Mage::helper('ddg')->getWebsiteConfig($path, $websiteId);
        $enabled = Mage::helper('ddg')->getWebsiteConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_API_ENABLED, $websiteId);

        //add customer to automation
        if ($enabled && $automationCampaignId) {
            Mage::helper( 'ddg' )->log( 'AS - ' . $automationType . ' automation Campaign id : ' . $automationCampaignId );
            $client = Mage::helper( 'ddg' )->getWebsiteApiClient( $websiteId );
            $apiContact = $client->postContacts($email);

            // get a program by id
            $program = $client->getProgramById($automationCampaignId);
            /**
             * id
             * name
             * status
             * dateCreated
             */
            Mage::helper( 'ddg' )->log( 'AS - get ' . $automationType . ' Program id : ' . $program->id);
            //check for active program with status "Active"
            if (isset($program->status) && $program->status == 'Active') {
                $data = array(
                    'Contacts' => array($apiContact->id),
                    'ProgramId'   => $program->id,
                    'Status'      => $program->status,
                    'DateCreated' => $program->dateCreated,
                    'AddressBooks' => array()
                );
                $client->postProgramsEnrolments($data);
            }
        }
    }

    /**
     * convert_quote_to_order observer
     *
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function handleQuoteToOrder(Varien_Event_Observer $observer)
    {
        /* @var $order Mage_Sales_Model_Order */
        $order = $observer->getOrder();
        $quoteId = $order->getQuoteId();
        $connectorQuote = Mage::getModel('ddg_automation/quote')->loadQuote($quoteId);
        if($connectorQuote)
            $connectorQuote->setModified(1)->setConvertedToOrder(1)->save();

        return $this;
    }

    /**
     * sales_quote_save_after event observer
     *
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function handleQuoteSaveAfter(Varien_Event_Observer $observer)
    {
        /* @var $quote Mage_Sales_Model_Quote */
        $quote = $observer->getEvent()->getQuote();
        if($quote->getCustomerId() && $quote->getAllItems() > 0) {
            $connectorQuote = Mage::getModel('ddg_automation/quote')->loadQuote($quote->getId());
            if($connectorQuote){
                if($connectorQuote->getImported())
                    $connectorQuote->setModified(1)->setImported(null)->save();
            }
            else
                $this->_registerQuote($quote);
        }
        return $this;
    }

    /**
     * register quote with connector
     *
     * @param Mage_Sales_Model_Quote $quote
     */
    private function _registerQuote(Mage_Sales_Model_Quote $quote)
    {
        try {
            $connectorQuote = Mage::getModel('ddg_automation/quote');
            $connectorQuote->setQuoteId($quote->getId())
                ->setCustomerId($quote->getCustomerId())
                ->setStoreId($quote->getStoreId())
                ->save();
        }catch (Exception $e){
            Mage::logException($e);
        }
    }
}