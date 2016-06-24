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


    //error messages
    const SEND_EMAIL_CONTACT_ID_MISSING = 'Error : missing contact id - will try later to send ';

    /**
     * constructor
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
        $collection->getSelect()->limit(1);

        if ($collection->getSize()) {
            return $collection->getFirstItem();
        } else {
            $this->setQuoteId($quoteId)
                ->setStoreId($storeId);
        }

        return $this;
    }


    /**
     * Sending the campaigns.
     */
    public function sendCampaigns()
    {
        //grab the emails not send
        $emailsToSend = $this->_getEmailCampaigns();

        foreach ($emailsToSend as $campaign) {

            $email      = $campaign->getEmail();
            $storeId    = $campaign->getStoreId();
            $campaignId = $campaign->getCampaignId();
            $store      = Mage::app()->getStore($storeId);
            $websiteId  = $store->getWebsiteId();


            if ( ! $campaignId) {
                $campaign->setMessage('Missing campaign id: ' . $campaignId)
                    ->setIsSent(1)
                    ->save();
                continue;
            } elseif ( ! $email) {
                $campaign->setMessage('Missing email : ' . $email)
                    ->setIsSent(1)
                    ->save();
                continue;
            }
            try {
                $client    = Mage::helper('ddg')->getWebsiteApiClient(
                    $websiteId
                );
                $contactId = Mage::helper('ddg')->getContactId(
                    $campaign->getEmail(), $websiteId
                );
                if (is_numeric($contactId)) {
                    //update data fields for order review camapigns
                    if ($campaign->getEventName() == 'Order Review') {
                        $website = Mage::app()->getWebsite($websiteId);
                        $order = Mage::getModel('sales/order')->loadByIncrementId($campaign->getOrderIncrementId());

                        if ($lastOrderId = $website->getConfig(
                            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOMER_LAST_ORDER_ID
                        )
                        ) {
                            $data[] = array(
                                'Key' => $lastOrderId,
                                'Value' => $order->getId()
                            );
                        }
                        if ($orderIncrementId = $website->getConfig(
                            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOMER_LAST_ORDER_INCREMENT_ID
                        )
                        ) {
                            $data[] = array(
                                'Key' => $orderIncrementId,
                                'Value' => $order->getIncrementId()
                            );
                        }

                        if (!empty($data)) {
                            //update data fields
                            $client->updateContactDatafieldsByEmail(
                                $email, $data
                            );
                        }
                    }

                    $response = $client->postCampaignsSend(
                        $campaignId, array($contactId)
                    );
                    if (isset($response->message)) {
                        //update  the failed to send email message
                        $campaign->setMessage($response->message)->setIsSent(1);
                    }
                    $now = Mage::getSingleton('core/date')->gmtDate();
                    //record suscces
                    $campaign->setIsSent(1)
                        ->setMessage(null)
                        ->setSentAt($now)
                        ->save();
                } else {
                    //update  the failed to send email message- error message from post contact
                    $campaign->setContactMessage($contactId)->setIsSent(1)
                        ->save();
                }
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
    }

    /**
     * @return mixed
     */
    protected function _getEmailCampaigns()
    {
        $emailCollection = $this->getCollection();
        $emailCollection->addFieldToFilter('is_sent', array('null' => true))
            ->addFieldToFilter('campaign_id', array('notnull' => true));
        $emailCollection->getSelect()->order('campaign_id');

        return $emailCollection;
    }
}