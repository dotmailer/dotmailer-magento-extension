<?php

class Dotdigitalgroup_Email_Model_Campaign extends Mage_Core_Model_Abstract
{
	//xml path configuration
	const XML_PATH_LOSTBASKET_1_ENABLED      = 'connector_lost_baskets/customers/enabled_1';
	const XML_PATH_LOSTBASKET_2_ENABLED      = 'connector_lost_baskets/customers/enabled_2';
	const XML_PATH_LOSTBASKET_3_ENABLED      = 'connector_lost_baskets/customers/enabled_3';

	const XML_PATH_LOSTBASKET_1_INTERVAL     = 'connector_lost_baskets/customers/send_after_1';
	const XML_PATH_LOSTBASKET_2_INTERVAL     = 'connector_lost_baskets/customers/send_after_2';
	const XML_PATH_LOSTBASKET_3_INTERVAL     = 'connector_lost_baskets/customers/send_after_3';

	const XML_PATH_TRIGGER_1_CAMPAIGN        = 'connector_lost_baskets/customers/campaign_1';
	const XML_PATH_TRIGGER_2_CAMPAIGN        = 'connector_lost_baskets/customers/campaign_2';
	const XML_PATH_TRIGGER_3_CAMPAIGN        = 'connector_lost_baskets/customers/campaign_3';

	const XML_PATH_GUEST_LOSTBASKET_1_ENABLED  = 'connector_lost_baskets/guests/enabled_1';
	const XML_PATH_GUEST_LOSTBASKET_2_ENABLED  = 'connector_lost_baskets/guests/enabled_2';
	const XML_PATH_GUEST_LOSTBASKET_3_ENABLED  = 'connector_lost_baskets/guests/enabled_3';

	const XML_PATH_GUEST_LOSTBASKET_1_INTERVAL = 'connector_lost_baskets/guests/send_after_1';
	const XML_PATH_GUEST_LOSTBASKET_2_INTERVAL = 'connector_lost_baskets/guests/send_after_2';
	const XML_PATH_GUEST_LOSTBASKET_3_INTERVAL = 'connector_lost_baskets/guests/send_after_3';

	const XML_PATH_GUEST_LOSTBASKET_1_CAMPAIGN = 'connector_lost_baskets/guests/campaign_1';
	const XML_PATH_GUEST_LOSTBASKET_2_CAMPAIGN = 'connector_lost_baskets/guests/campaign_2';
	const XML_PATH_GUEST_LOSTBASKET_3_CAMPAIGN = 'connector_lost_baskets/guests/campaign_3';


	//error messages
	const SEND_EMAIL_CONTACT_ID_MISSING = 'Error : missing contact id - will try later to send ';

	//campaign create vars
	private $fromAddress;
	private $replyAction;
	private $replyAddress;
	private $copyEmail;

	/**
	 * @var object
	 */
	public $transactionalClient;

	/**
	 * constructor
	 */
	public function _construct()
	{
		parent::_construct();
		$this->_init('email_connector/campaign');

		$this->transactionalClient = Mage::helper('connector/transactional')->getWebsiteApiClient();
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
	 * @return mixed
	 */
	public function loadByQuoteId($quoteId, $storeId)
	{
		$collection = $this->getCollection()
		                   ->addFieldToFilter('quote_id', $quoteId)
		                   ->addFieldToFilter('store_id', $storeId);

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
		//create campaign first
		$this->createEmailsToCampaigns();

		//grab the emails not send
		$emailsToSend = $this->_getEmailCampaigns();
		$templateModel = Mage::getModel('email_connector/email_template');

		foreach ($emailsToSend as $campaign) {

			$email      = $campaign->getEmail();
			$storeId    = $campaign->getStoreId();
			$campaignId = $campaign->getCampaignId();
			$store = Mage::app()->getStore($storeId);
			$websiteId      = $store->getWebsiteId();
			$storeName      = $store->getName();
			$websiteName    = $store->getWebsite()->getName();


			if (!$campaignId) {
				$campaign->setMessage('Missing campaign id: ' . $campaignId)
				         ->setIsSent(1)
				         ->save();
				continue;
			} elseif (!$email) {
				$campaign->setMessage('Missing email : ' . $email)
				         ->setIsSent(1)
				         ->save();
				continue;
			}
			try{
				if ($campaign->getEventName() == 'Lost Basket') {
					$client = Mage::helper('connector')->getWebsiteApiClient($websiteId);
					$contactId = Mage::helper('connector')->getContactId($campaign->getEmail(), $websiteId);
					if(is_numeric($contactId)) {

                        //update data field
                        $data = array(
                            array(
                                'Key' => 'LAST_QUOTE_ID',
                                'Value' => $campaign->getQuoteId()
                            )
                        );
                        $client->updateContactDatafieldsByEmail($campaign->getEmail(), $data);

						$response = $client->postCampaignsSend($campaignId, array($contactId));
						if (isset($response->message)) {
							//update  the failed to send email message
							$campaign->setMessage($response->message)->setIsSent(1)->save();
						}
						$now = Mage::getSingleton('core/date')->gmtDate();
						//record suscces
						$campaign->setIsSent(1)
						         ->setMessage(NULL)
						         ->setSentAt($now)
						         ->save();
					}else{
						//update  the failed to send email message- error message from post contact
						$campaign->setContactMessage($contactId)->setIsSent(1)->save();
					}
				} elseif ($campaign->getEventName() == 'New Customer Account') {
					$contactId = Mage::helper('connector/transactional')->getContactId($campaign->getEmail(), $websiteId);
					if(is_numeric($contactId)){
						Mage::helper('connector')->log($contactId);
						$customerId = $campaign->getCustomerId();
						$customer = Mage::getModel('customer/customer')->load($customerId);
						$firstname = $customer->getFirstname();
						$lastname = $customer->getLastname();
						$data = array(
							array(
								'Key' => 'STORE_NAME',
								'Value' => $storeName),
							array(
								'Key' => 'WEBSITE_NAME',
								'Value' => $websiteName),
							array(
								'Key' => 'FIRSTNAME',
								'Value' => $firstname),
							array(
								'Key' => 'LASTNAME',
								'Value' => $lastname),
							array(
								'Key' => 'CUSTOMER_ID',
								'Value' => $customerId)
						);
                        // last order and last quote
                        $emailOrder = Mage::getModel('email_connector/sales_order');
                        $lastOrder = $emailOrder->getCustomerLastOrderId($customer);
                        $lastQuote = $emailOrder->getCustomerLastQuoteId($customer);

                        if($lastOrder){
                            $data[] = array(
                                'Key' => 'LAST_ORDER_ID',
                                'Value' => $lastOrder->getId()
                            );
                        }
                        if($lastQuote){
                            $data[] = array(
                                'Key' => 'LAST_QUOTE_ID',
                                'Value' => $lastQuote->getId()
                            );
                        }

						$this->transactionalClient->updateContactDatafieldsByEmail($email, $data);

						$response = $this->transactionalClient->postCampaignsSend($campaignId, array($contactId));
						if (isset($response->message)) {
							//update  the failed to send email message
							$campaign->setMessage($response->message)->setIsSent(1)->save();
						} else {
							$now = Mage::getSingleton('core/date')->gmtDate();
							//record suscces
							$campaign->setIsSent(1)
							         ->setMessage(NULL)
							         ->setSentAt($now)
							         ->save();
						}
					}else{
						//update  the failed to send email message- error message from post contact
						$campaign->setContactMessage($contactId)->setIsSent(1)->save();
					}

				} elseif ($templateModel->getSalesEvent($campaign->getEventName()) or $campaign->getEventName() == 'Order Review') {
					// transactional
					$orderModel = Mage::getModel("sales/order")->loadByIncrementId($campaign->getOrderIncrementId());
					$contactId = Mage::helper('connector/transactional')->getContactId($campaign->getEmail(), $websiteId);
					if (is_numeric($contactId)) {
						Mage::helper('connector')->log($contactId);
						if ($orderModel->getCustomerId()) {
							$firstname = $orderModel->getCustomerFirstname();
							$lastname = $orderModel->getCustomerLastname();
						} else {
							$billing = $orderModel->getBillingAddress();
							$firstname = $billing->getFirstname();
							$lastname = $billing->getLastname();
						}
						$data = array(
							array(
								'Key' => 'STORE_NAME',
								'Value' => $storeName),
							array(
								'Key' => 'WEBSITE_NAME',
								'Value' => $websiteName),
							array(
								'Key' => 'FIRSTNAME',
								'Value' => $firstname),
							array(
								'Key' => 'LASTNAME',
								'Value' => $lastname),
							array(
								'Key' => 'LAST_ORDER_ID',
								'Value' => $orderModel->getId()),
                            array(
                                'Key' => 'LAST_QUOTE_ID',
                                'Value' => $orderModel->getQuoteId()),
						);
						$this->transactionalClient->updateContactDatafieldsByEmail($email, $data);
						$response = $this->transactionalClient->postCampaignsSend($campaignId, array($contactId));
						if (isset($response->message)) {
							//update  the failed to send email message
							$campaign->setMessage($response->message)->setIsSent(1)->save();
						} else {
							$now = Mage::getSingleton('core/date')->gmtDate();
							//record suscces
							$campaign->setIsSent(1)
							         ->setMessage(NULL)
							         ->setSentAt($now)
							         ->save();
						}
					}else{
						//update  the failed to send email message- error message from post contact
						$campaign->setContactMessage($contactId)->setIsSent(1)->save();
					}
				}else{
					$contactId = Mage::helper('connector/transactional')->getContactId($campaign->getEmail(), $websiteId);
					if(is_numeric($contactId)){
						Mage::helper('connector')->log($contactId);
						$response = $this->transactionalClient->postCampaignsSend($campaignId, array($contactId));
						if (isset($response->message)) {
							//update  the failed to send email message
							$campaign->setMessage($response->message)->setIsSent(1)->save();
						} else{
							$now = Mage::getSingleton('core/date')->gmtDate();
							//record suscces
							$campaign->setIsSent(1)
							         ->setMessage(NULL)
							         ->setSentAt($now)
							         ->save();
						}
					}else{
						//update  the failed to send email message- error message from post contact
						$campaign->setContactMessage($contactId)->setIsSent(1)->save();
					}
				}

			}catch(Exception $e){
				Mage::logException($e);
			}
		}
		return;
	}

	/**
	 * @return mixed
	 */
	private function _getEmailCampaigns()
	{
		$emailCollection = $this->getCollection();
		$emailCollection->addFieldToFilter('is_sent', array('null' => true))
		                ->addFieldToFilter('campaign_id', array('notnull' => true))
		                ->addFieldToFilter('type', 1);
		$emailCollection->getSelect()->order('campaign_id');
		return $emailCollection;
	}

	/**
	 * create emails to campaigns
	 */
	public function createEmailsToCampaigns()
	{
		$helper = Mage::helper('connector/transactional');
		$emails = $this->getEmailsToCreateCampaigns();

		foreach($emails as $email)
		{
			try {
				$websiteId = $email->getWebsiteId();

				if (!$this->fromAddress) {
                    if($email->getFromAddress())
                        $this->fromAddress = $email->getFromAddress();
                    else
                        $this->fromAddress = $helper->getFromAddress($websiteId);
                }

				if (!$this->replyAction)
					$this->replyAction = $helper->getReplyAction($websiteId);
				if ($this->replyAction == 'WebMailForward') {
					if (!$this->replyAddress) {
						$this->replyAddress = $helper->getReplyAddress($websiteId);
					}
				}
				if (!$this->copyEmail)
					$this->copyEmail = $helper->getSendCopy($websiteId);

				if ($this->fromAddress && $this->replyAction) {
					$data = array(
						'Name' => $email->getEventName(),
						'Subject' => $email->getSubject(),
						'FromName' => $email->getFromName(),
						'FromAddress' => $this->fromAddress,
						'HtmlContent' => $email->getHtmlContent(),
						'PlainTextContent' => $email->getPlainTextContent(),
						'ReplyAction' => $this->replyAction,
						'IsSplitTest' => false,
						'Status' => 'Unsent'
					);
					if ($this->replyAction == 'WebMailForward' && $this->replyAddress)
						$data['ReplyToAddress'] = $this->replyAddress;
					else
						$data['ReplyToAddress'] = '';
				}

				if(isset($data)){
					$client = Mage::helper('connector/transactional')->getWebsiteApiClient($websiteId);
					$result = $client->postCampaign($data);
					if (isset($result->message)) {
						$email->setCreateMessage($result->message)
						      ->setIsCreated(1)
						      ->save();
						continue;
					}
					Mage::helper('connector')->log('createEmailsToCampaigns ' . $result->id);

                    //attachment to campaign
                    if($email->getAttachmentId()){
                        $attachment = array(
                            'id' => $email->getAttachmentId()
                        );
                        $attachmentResult = $client->postCampaignAttachments($result->id, $attachment);
                        if (isset($attachmentResult->message)) {
                            $email->setCreateMessage($attachmentResult->message)
                                ->setIsCreated(1)
                                ->save();
                            continue;
                        }
                    }

					$email->setCampaignId($result->id)
					      ->setType(1)
					      ->setIsCreated(1)
					      ->save();

					if($this->copyEmail){
						$this->setEmail($this->copyEmail)
						     ->setIsCopy(1)
						     ->setCampaignId($result->id)
						     ->setEventName($email->getEventName())
						     ->setType(1)
						     ->setIsCreated(1)
						     ->save();
					}
				}
			}catch(Exception $e){
				Mage::logException($e);
			}
		}
	}

	/**
	 * get collection to create campaigns
	 *
	 * @param int $pageSize
	 * @return Mage_Eav_Model_Entity_Collection_Abstract
	 */
	protected function getEmailsToCreateCampaigns($pageSize = 100)
	{
		$collection =  $this->getCollection()
		                    ->addFieldToFilter('is_created', array('null' => true))
		                    ->addFieldToFilter('type', 2);
		$collection->getSelect()->limit($pageSize);
		return $collection;
	}
}