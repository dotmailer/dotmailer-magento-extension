<?php

class Dotdigitalgroup_Email_Model_Campaign extends Mage_Core_Model_Abstract
{

    //xml path configuration
    const XML_PATH_LOSTBASKET_1_ENABLED = 'connector_lost_baskets/customers/enabled_1';
    const XML_PATH_LOSTBASKET_2_ENABLED = 'connector_lost_baskets/customers/enabled_2';
    const XML_PATH_LOSTBASKET_3_ENABLED = 'connector_lost_baskets/customers/enabled_3';

    const XML_PATH_LOSTBASKET_1_INTERVAL = 'connector_lost_baskets/customers/send_after_1';
    const XML_PATH_LOSTBASKET_2_INTERVAL = 'connector_lost_baskets/customers/send_after_2';
    const XML_PATH_LOSTBASKET_3_INTERVAL = 'connector_lost_baskets/customers/send_after_3';

    const XML_PATH_TRIGGER_1_CAMPAIGN = 'connector_lost_baskets/customers/campaign_1';
    const XML_PATH_TRIGGER_2_CAMPAIGN = 'connector_lost_baskets/customers/campaign_2';
    const XML_PATH_TRIGGER_3_CAMPAIGN = 'connector_lost_baskets/customers/campaign_3';

    const XML_PATH_GUEST_LOSTBASKET_1_ENABLED = 'connector_lost_baskets/guests/enabled_1';
    const XML_PATH_GUEST_LOSTBASKET_2_ENABLED = 'connector_lost_baskets/guests/enabled_2';
    const XML_PATH_GUEST_LOSTBASKET_3_ENABLED = 'connector_lost_baskets/guests/enabled_3';

    const XML_PATH_GUEST_LOSTBASKET_1_INTERVAL = 'connector_lost_baskets/guests/send_after_1';
    const XML_PATH_GUEST_LOSTBASKET_2_INTERVAL = 'connector_lost_baskets/guests/send_after_2';
    const XML_PATH_GUEST_LOSTBASKET_3_INTERVAL = 'connector_lost_baskets/guests/send_after_3';

    const XML_PATH_GUEST_LOSTBASKET_1_CAMPAIGN = 'connector_lost_baskets/guests/campaign_1';
    const XML_PATH_GUEST_LOSTBASKET_2_CAMPAIGN = 'connector_lost_baskets/guests/campaign_2';
    const XML_PATH_GUEST_LOSTBASKET_3_CAMPAIGN = 'connector_lost_baskets/guests/campaign_3';

    const SENT = 2;
    const FAILED = 3;
    const PENDING = 0;
    const PROCESSING = 1;

    const CAMPAIGN_EVENT_ORDER_REVIEW = 'Order Review';
    const CAMPAIGN_EVENT_LOST_BASKET = 'Lost Basket';

    //error messages
    const SEND_EMAIL_CONTACT_ID_MISSING = 'Error : missing contact id - will try later to send ';

    //single call contact limit
    const SEND_EMAIL_CONTACT_LIMIT = 10;

    /**
     * Constructor.
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('ddg_automation/campaign');
    }

    /**
     * @return $this|Mage_Core_Model_Abstract
     */
    protected function _beforeSave()
    {
        parent::_beforeSave();
        $now = Mage::getSingleton('core/date')->gmtDate();
        if ($this->isObjectNew()) {
            $this->setCreatedAt($now);
        }

        $this->setUpdatedAt($now);

        return $this;
    }

    /**
     * @param $quoteId
     * @param $storeId
     *
     * @return mixed
     */
    public function loadByQuoteId($quoteId, $storeId)
    {
        $collection = $this->getCollection()
            ->addFieldToFilter('quote_id', $quoteId)
            ->addFieldToFilter('store_id', $storeId);
        //@codingStandardsIgnoreStart
        $collection->getSelect()->limit(1);

        if ($collection->getSize()) {
            return $collection->getFirstItem();
        } else {
            $this->setQuoteId($quoteId)
                ->setStoreId($storeId);
        }
        //@codingStandardsIgnoreEnd

        return $this;
    }

    /**
     * @param $website
     */
    protected function _checkSendStatus($website)
    {
        $this->expireExpiredCampaigns($website->getStoreIds());
        $campaigns = $this->_getEmailCampaigns($website->getStoreIds(), self::PROCESSING, true);
        foreach ($campaigns as $campaign) {
            $client = Mage::helper('ddg')->getWebsiteApiClient($website);
            if ($client) {
                $response = $client->getSendStatus($campaign->getSendId());
                if (isset($response->message) || $response->status == 'Cancelled') {
                    //update  the failed to send email message
                    $message = isset($response->message) ? $response->message : $response->status;
                    $this->getResource()->setMessageWithSendId($campaign->getSendId(), $message);
                } elseif ($response->status == 'Sent') {
                    $this->getResource()->setSent($campaign->getSendId());
                }
            }
        }
    }

    /**
     * @param array $storeIds
     */
    protected function expireExpiredCampaigns($storeIds)
    {
        $expiredCampaigns = $this->getExpiredEmailCampaignsByStoreIds($storeIds);
        $ids = $expiredCampaigns->getColumnValues('id');
        if (! empty($ids)) {
            $this->getResource()->expireCampaigns($ids);
        }
    }


    /**
     * Sending the campaigns.
     *
     * @codingStandardsIgnoreStart
     */
    public function sendCampaigns()
    {
        /** @var Mage_Core_Model_Website $website */
        foreach (Mage::app()->getWebsites(true) as $website) {
            //check send status for processing
            $this->_checkSendStatus($website);
            //start send process
            $emailsToSend = $this->_getEmailCampaigns($website->getStoreIds());
            $campaignsToSend = array();

            if ($this->hasOrderReviews($emailsToSend)) {
                $salesRecords = $this->getOrderObjectsAsArray($emailsToSend);
            }

            foreach ($emailsToSend as $campaign) {
                $email = $campaign->getEmail();
                $campaignId = $campaign->getCampaignId();
                $websiteId = $website->getId();
                $client = Mage::helper('ddg')->getWebsiteApiClient(
                    $websiteId
                );

                //Only if valid client is returned
                if ($client) {
                    if (!$campaignId) {
                        $campaign->setMessage('Missing campaign id: ' . $campaignId)
                            ->setSendStatus(self::FAILED)
                            ->save();
                        continue;
                    } elseif (!$email) {
                        $campaign->setMessage('Missing email')
                            ->setSendStatus(self::FAILED)
                            ->save();
                        continue;
                    }

                    $campaignsToSend[$campaignId]['client'] = $client;
                    try {
                        $contactId = Mage::helper('ddg')->getContactId(
                            $campaign->getEmail(),
                            $websiteId
                        );
                        if (is_numeric($contactId)) {
                            //update data fields
                            if ($campaign->getEventName() == self::CAMPAIGN_EVENT_ORDER_REVIEW && isset($salesRecords)) {

                                $order = array_filter($salesRecords, function ($record) use ($campaign) {
                                    if (in_array($campaign->getOrderIncrementId(), $record)) {
                                        return $record;
                                    }
                                });

                                $order = reset($order);

                                if ($lastOrderId = $website->getConfig(
                                    Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOMER_LAST_ORDER_ID
                                )
                                ) {
                                    $data[] = array(
                                        'Key' => $lastOrderId,
                                        'Value' => $order['entity_id']
                                    );
                                }

                                if ($orderIncrementId = $website->getConfig(
                                    Dotdigitalgroup_Email_Helper_Config::
                                    XML_PATH_CONNECTOR_CUSTOMER_LAST_ORDER_INCREMENT_ID
                                )
                                ) {
                                    $data[] = array(
                                        'Key' => $orderIncrementId,
                                        'Value' => $order['increment_id']
                                    );
                                }

                                if (!empty($data)) {
                                    //update data fields
                                    $client->updateContactDatafieldsByEmail(
                                        $email,
                                        $data
                                    );
                                }
                            } elseif (
                                $campaign->getEventName() == self::CAMPAIGN_EVENT_LOST_BASKET
                            ) {
                                $campaignCollection = $this->getCollection();
                                // Skip if AC campaigns found with status processing for email in current cron run
                                if ($campaignCollection->getNumberOfProcessingAcCampaignsForContact($email)) {
                                    continue;
                                }
                                Mage::helper('ddg')->updateLastQuoteId(
                                    $campaign->getQuoteId(),
                                    $email,
                                    $websiteId
                                );
                            }

                            $campaignsToSend[$campaignId]['contacts'][] = $contactId;
                            $campaignsToSend[$campaignId]['ids'][] = $campaign->getId();
                        } else {
                            //update the failed to send email message error message
                            $campaign->setSendStatus(self::FAILED)
                                ->setMessage('Send not permitted. Contact is suppressed.')
                                ->save();
                        }
                    } catch (Exception $e) {
                        Mage::logException($e);
                    }
                }
            }

            foreach ($campaignsToSend as $campaignId => $data) {
                if (isset($data['contacts']) && isset($data['client'])) {
                    $contacts = $data['contacts'];
                    $client = $data['client'];
                    $response = $client->postCampaignsSend(
                        $campaignId,
                        $contacts
                    );
                    if (isset($response->message)) {
                        //update  the failed to send email message
                        $this->getResource()->setMessage($data['ids'], $response->message);
                    } elseif (isset($response->id)) {
                        $this->getResource()->setProcessing($data['ids'], $response->id);
                    } else {
                        //update  the failed to send email message
                        $this->getResource()->setMessage($data['ids'], 'No send id returned.');
                    }
                }
            }
        }
        //@codingStandardsIgnoreEnd
    }

    /**
     * Get campaign collection.
     *
     * @param $storeIds
     * @param $sendStatus
     * @param $sendIdCheck
     * @return Dotdigitalgroup_Email_Model_Resource_Campaign_Collection
     */
    protected function _getEmailCampaigns($storeIds, $sendStatus = 0, $sendIdCheck = false)
    {
        $emailCollection = $this->getCollection();
        $emailCollection->addFieldToFilter('send_status', array('eq' => $sendStatus))
            ->addFieldToFilter('campaign_id', array('notnull' => true))
            ->addFieldToFilter('store_id', array('in' => $storeIds));

        //@codingStandardsIgnoreStart
        //check for send id
        if ($sendIdCheck) {
            $emailCollection->addFieldToFilter('send_id', array('notnull' => true))
                ->getSelect()
                ->group('send_id');
        } else {
            $emailCollection->getSelect()
                ->order('campaign_id');
        }

        $emailCollection->getSelect()->limit(self::SEND_EMAIL_CONTACT_LIMIT);

        //@codingStandardsIgnoreEnd
        return $emailCollection;
    }

    /**
     * @param $collection
     */
    protected function hasOrderReviews($collection)
    {
        return in_array(
            self::CAMPAIGN_EVENT_ORDER_REVIEW,
            $collection->getColumnValues('event_name')
        );
    }

    /**
     * @param $emailCollection
     * @return mixed
     */
    protected function getOrderObjectsAsArray($emailCollection)
    {
        $orderIncrementIds = $emailCollection->getColumnValues('order_increment_id');
        $salesCollection = Mage::getModel('sales/order')
            ->getCollection()
            ->addFieldToFilter('increment_id', array('in' => $orderIncrementIds));

        $salesCollection = $salesCollection->toArray();
        return $salesCollection['items'];
    }

    /**
     * @param array $storeIds
     * @return Dotdigitalgroup_Email_Model_Resource_Campaign_Collection
     */
    protected function getExpiredEmailCampaignsByStoreIds($storeIds)
    {
        $time = Zend_Date::now(Mage::app()->getLocale()->getLocale())->subHour(2);
        $campaignCollection = $this->getCollection()
            ->addFieldToFilter('campaign_id', array('notnull' => true))
            ->addFieldToFilter('send_status', Dotdigitalgroup_Email_Model_Campaign::PROCESSING)
            ->addFieldToFilter('store_id', array('in' => $storeIds))
            ->addFieldToFilter('send_id', array('notnull' => true))
            ->addFieldToFilter('updated_at', array('lt' => $time->toString('yyyy-MM-dd HH:mm')));

        return $campaignCollection;
    }
}
