<?php

class Dotdigitalgroup_Email_Model_Sales_Observer
{

    /**
     * Save/reset the order as transactional data.
     *
     * @param Varien_Event_Observer $observer
     *
     * @return $this
     */
    public function handleSalesOrderSaveAfter(Varien_Event_Observer $observer)
    {
        /** @var $order Mage_Sales_Model_Order */
        $order                  = $observer->getEvent()->getOrder();
        $status                 = $order->getStatus();
        $storeId                = $order->getStoreId();
        $appEmulation           = Mage::getSingleton('core/app_emulation');
        $initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation(
            $storeId
        );
        try {
            $store         = Mage::app()->getStore($storeId);
            $storeName     = $store->getName();
            $websiteId     = $store->getWebsiteId();
            $customerEmail = $order->getCustomerEmail();
            // start app emulation
            $emailOrder = Mage::getModel('ddg_automation/order')->loadByOrderId(
                $order->getEntityId(), $order->getQuoteId()
            );
            //reimport email order
            $emailOrder->setUpdatedAt($order->getUpdatedAt())
                ->setStoreId($storeId)
                ->setOrderStatus($status);
            if ($emailOrder->getEmailImported()
                != Dotdigitalgroup_Email_Model_Contact::EMAIL_CONTACT_IMPORTED
            ) {
                $emailOrder->setEmailImported(null);
            }

            //if api is not enabled
            if (!$store->getWebsite()->getConfig(
                Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_API_ENABLED
            )
            ) {
                $appEmulation->stopEnvironmentEmulation(
                    $initialEnvironmentInfo
                );

                return $this;
            }

            // check for order status change
            $statusBefore = $order->getOrigData('status');
            //check if order status changed
            if ($status != $statusBefore) {
                //If order status has changed and order is already imported then set modified to 1
                if ($emailOrder->getEmailImported()
                    == Dotdigitalgroup_Email_Model_Contact::EMAIL_CONTACT_IMPORTED
                ) {
                    $emailOrder->setModified(
                        Dotdigitalgroup_Email_Model_Contact::EMAIL_CONTACT_IMPORTED
                    );
                }
            }

            // set back the current store
            $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);
            $emailOrder->save();
            //@codingStandardsIgnoreStart
            //Status check automation enrolment
            $configStatusAutomationMap = unserialize(
                Mage::getStoreConfig(
                    Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_AUTOMATION_STUDIO_ORDER_STATUS,
                    $order->getStore()
                )
            );
            if (!empty($configStatusAutomationMap)) {
                foreach ($configStatusAutomationMap as $configMap) {
                    if ($configMap['status'] == $status) {
                        try {
                            $programId  = $configMap['automation'];
                            $automation = Mage::getModel('ddg_automation/automation');
                            $automation->setEmail($customerEmail)
                                ->setAutomationType('order_automation_' . $status)
                                ->setEnrolmentStatus(
                                    Dotdigitalgroup_Email_Model_Automation::AUTOMATION_STATUS_PENDING
                                )
                                ->setTypeId($order->getId())
                                ->setWebsiteId($websiteId)
                                ->setStoreName($storeName)
                                ->setProgramId($programId);
                            $automation->save();
                        } catch (Exception $e) {
                            Mage::logException($e);
                        }
                    }
                }
            }
            //@codingStandardsIgnoreEnd
        } catch (Exception $e) {
            Mage::logException($e);
            $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);
        }

        return $this;
    }


    /**
     * Create new order event.
     *
     * @param Varien_Event_Observer $observer
     *
     * @return $this
     * @throws Mage_Core_Exception
     */
    public function handleSalesOrderPlaceAfter(Varien_Event_Observer $observer)
    {
        /** @var $order Mage_Sales_Model_Order */
        $order     = $observer->getEvent()->getOrder();
        $email     = $order->getCustomerEmail();
        $website   = Mage::app()->getWebsite($order->getWebsiteId());
        $storeName = Mage::app()->getStore($order->getStoreId())->getName();

        //if api is not enabled
        if (!$website->getConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_API_ENABLED)) {
            return $this;
        }

        //automation enrolment for order
        if ($order->getCustomerIsGuest()) {
            // guest to automation mapped
            $programType = 'XML_PATH_CONNECTOR_AUTOMATION_STUDIO_GUEST_ORDER';
            $automationType = Dotdigitalgroup_Email_Model_Automation::AUTOMATION_TYPE_NEW_GUEST_ORDER;
        } else {
            // customer to automation mapped
            $customerAutomationMapped 
                = Mage::getStoreConfig('connector_automation_studio/visitor_automation/first_order_automation');
            if( $customerAutomationMapped == '0' || is_null($customerAutomationMapped) ) {
                return $this;
            }

            $programType = 'XML_PATH_CONNECTOR_AUTOMATION_STUDIO_ORDER';
            $automationType = Dotdigitalgroup_Email_Model_Automation::AUTOMATION_TYPE_NEW_ORDER;

            if ($order->getCustomerId()) {
                //If customer's first order
                $orders = Mage::getModel('sales/order')
                    ->getCollection()
                    ->addFieldToFilter('customer_id', $order->getCustomerId());

                if ($orders->getSize() == 1) {
                    $automationTypeNewOrder
                        = Dotdigitalgroup_Email_Model_Automation::AUTOMATION_TYPE_CUSTOMER_FIRST_ORDER;
                    $programIdNewOrder = Mage::helper('ddg')->getAutomationIdByType(
                        'XML_PATH_CONNECTOR_AUTOMATION_STUDIO_NEW_ORDER', $order->getWebsiteId()
                    );
                    //program for customer new order mapped
                    if ($programIdNewOrder) {
                        //send to automation queue
                        $this->_doAutomationEnrolment(
                            array(
                                'programId' => $programIdNewOrder,
                                'automationType' => $automationTypeNewOrder,
                                'email' => $email,
                                'order_id' => $order->getId(),
                                'website_id' => $website->getId(),
                                'store_name' => $storeName
                            )
                        );
                    }
                }
            }
        }

        $programId = Mage::helper('ddg')->getAutomationIdByType($programType, $order->getWebsiteId());

        if ($programId) {
            //send to automation queue
            $this->_doAutomationEnrolment(
                array(
                    'programId' => $programId,
                    'automationType' => $automationType,
                    'email' => $email,
                    'order_id' => $order->getId(),
                    'website_id' => $website->getId(),
                    'store_name' => $storeName
                )
            );
        }

        return $this;
    }

    /**
     * @param $data
     */
    protected function _doAutomationEnrolment($data)
    {
        try {
            $automation = Mage::getModel('ddg_automation/automation');
            $automation->setEmail($data['email'])
                ->setAutomationType($data['automationType'])
                ->setEnrolmentStatus(
                    Dotdigitalgroup_Email_Model_Automation::AUTOMATION_STATUS_PENDING
                )
                ->setTypeId($data['order_id'])
                ->setWebsiteId($data['website_id'])
                ->setStoreName($data['store_name'])
                ->setProgramId($data['programId'])
                ->save();
        } catch (Exception $e) {
            Mage::logException($e);
        }
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
        $storeId    = $creditmemo->getStoreId();
        $order      = $creditmemo->getOrder();
        $orderId    = $order->getEntityId();
        $quoteId    = $order->getQuoteId();

        try {
            /**
             * Reimport transactional data.
             */
            $emailOrder = Mage::getModel('ddg_automation/order')->loadByOrderId(
                $orderId, $quoteId, $storeId
            );
            if (!$emailOrder->getId()) {
                Mage::helper('ddg')->log(
                    'ERROR Creditmemmo Order not found :' . $orderId
                    . ', quote id : ' . $quoteId . ', store id ' . $storeId
                );

                return $this;
            }

            $emailOrder->setEmailImported(
                Dotdigitalgroup_Email_Model_Contact::EMAIL_CONTACT_NOT_IMPORTED
            )->save();
        } catch (Exception $e) {
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
        $helper      = Mage::helper('ddg');
        $order       = $observer->getEvent()->getOrder();
        $incrementId = $order->getIncrementId();
        $websiteId   = Mage::app()->getStore($order->getStoreId())
            ->getWebsiteId();

        //sync enabled
        $syncEnabled = $helper->getWebsiteConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_ORDER_ENABLED,
            $websiteId
        );

        if ($helper->isEnabled($websiteId) && $syncEnabled) {
            //register in queue with importer
            Mage::getModel('ddg_automation/importer')->registerQueue(
                Dotdigitalgroup_Email_Model_Importer::IMPORT_TYPE_ORDERS,
                array($incrementId),
                Dotdigitalgroup_Email_Model_Importer::MODE_SINGLE_DELETE,
                $websiteId
            );
        }

        return $this;
    }

    /**
     * Convert_quote_to_order observer.
     *
     * @param Varien_Event_Observer $observer
     *
     * @return $this
     */
    public function handleQuoteToOrder(Varien_Event_Observer $observer)
    {
        $order       = $observer->getOrder();
        $helper      = Mage::helper('ddg');
        $enabled     = $helper->getWebsiteConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_API_ENABLED,
            $order->getStore()->getWebsiteId()
        );
        $syncEnabled = $helper->getWebsiteConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_QUOTE_ENABLED,
            $order->getStore()->getWebsiteId()
        );
        if ($enabled && $syncEnabled) {
            $quoteId        = $order->getQuoteId();
            $connectorQuote = Mage::getModel('ddg_automation/quote')->loadQuote(
                $quoteId
            );
            if ($connectorQuote) {
                //register in queue with importer for single delete
                Mage::getModel('ddg_automation/importer')->registerQueue(
                    Dotdigitalgroup_Email_Model_Importer::IMPORT_TYPE_QUOTE,
                    array($connectorQuote->getQuoteId()),
                    Dotdigitalgroup_Email_Model_Importer::MODE_SINGLE_DELETE,
                    $order->getStore()->getWebsiteId()
                );
                //delete from table
                $connectorQuote->delete();
            }
        }

        return $this;
    }

    /**
     * Sales_quote_save_after event observer.
     *
     * @param Varien_Event_Observer $observer
     *
     * @return $this
     */
    public function handleQuoteSaveAfter(Varien_Event_Observer $observer)
    {
        $quote       = $observer->getEvent()->getQuote();
        $helper      = Mage::helper('ddg');
        $enabled     = $helper->getWebsiteConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_API_ENABLED,
            $quote->getStore()->getWebsiteId()
        );
        $syncEnabled = $helper->getWebsiteConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_QUOTE_ENABLED,
            $quote->getStore()->getWebsiteId()
        );
        if ($enabled && $syncEnabled) {
            if ($quote->getCustomerId()) {
                $connectorQuote = Mage::getModel('ddg_automation/quote')
                    ->loadQuote($quote->getId());
                $count          = count($quote->getAllItems());
                if ($connectorQuote) {
                    if ($connectorQuote->getImported() && $count > 0) {
                        $connectorQuote->setModified(1)->save();
                    } elseif ($connectorQuote->getImported() && $count == 0) {
                        //register in queue with importer for single delete
                        Mage::getModel('ddg_automation/importer')
                            ->registerQueue(
                                Dotdigitalgroup_Email_Model_Importer::IMPORT_TYPE_QUOTE,
                                array($connectorQuote->getQuoteId()),
                                Dotdigitalgroup_Email_Model_Importer::MODE_SINGLE_DELETE,
                                $quote->getStore()->getWebsiteId()
                            );
                        //delete from table
                        $connectorQuote->delete();
                    }
                } elseif ($count > 0) {
                    $this->_registerQuote($quote);
                }
            }
        }

        return $this;
    }

    /**
     * Register quote with connector.
     *
     * @param Mage_Sales_Model_Quote $quote
     */
    protected function _registerQuote(Mage_Sales_Model_Quote $quote)
    {
        try {
            $connectorQuote = Mage::getModel('ddg_automation/quote');
            $connectorQuote->setQuoteId($quote->getId())
                ->setCustomerId($quote->getCustomerId())
                ->setStoreId($quote->getStoreId())
                ->save();
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }
}