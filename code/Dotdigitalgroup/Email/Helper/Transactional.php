<?php

class Dotdigitalgroup_Email_Helper_Transactional extends Mage_Core_Helper_Abstract
{
    const XML_PATH_TRANSACTIONAL_API_ENABLED                    = 'connector_transactional_emails/credentials/enabled';
    const XML_PATH_TRANSACTIONAL_API_USERNAME                   = 'connector_transactional_emails/credentials/api_username';
    const XML_PATH_TRANSACTIONAL_API_PASSWORD                   = 'connector_transactional_emails/credentials/api_password';

    const XML_PATH_CONNECTOR_TRANSACTIONAL_FROM_ADDRESS         = 'connector_transactional_emails/email_settings/from_address';
    const XML_PATH_CONNECTOR_TRANSACTIONAL_REPLY_ACTION         = 'connector_transactional_emails/email_settings/reply_action';
    const XML_PATH_CONNECTOR_TRANSACTIONAL_REPLY_ADDRESS        = 'connector_transactional_emails/email_settings/reply_address';
    const XML_PATH_CONNECTOR_TRANSACTIONAL_SEND_COPY            = 'connector_transactional_emails/email_settings/send_copy';
    const XML_PATH_CONNECTOR_TRANSACTIONAL_UNSUBSCRIBE_LINK     = 'connector_transactional_emails/email_settings/unsubscribe_link';

    const XML_PATH_CONNECTOR_TRANSACTIONAL_EMAIL_DEFAULT        = 'connector_transactional_emails/email_mapping/default_email_templates';
    const XML_PATH_CONNECTOR_TRANSACTIONAL_EMAIL_CUSTOM         = 'connector_transactional_emails/email_mapping/custom_email_templates';

	const MAP_COLUMN_KEY_TEMPLATE                               = 'template';
	const MAP_COLUMN_KEY_SENDTYPE                               = 'sendtype';
	const MAP_COLUMN_KEY_CAMPAIGN                               = 'campaign';
	const MAP_COLUMN_KEY_DATAFIELD                              = 'datafield';
    const MAP_COLUMN_KEY_FROM_ADDRESS                           = 'fromaddress';
    const MAP_COLUMN_KEY_ATTACHMENT_ID                          = 'attachmentid';

	const TRANSACTIONAL_SENDTYPE_SYSTEM_DEFAULT                 = '0';
	const TRANSACTIONAL_SENDTYPE_VIA_CONNECTOR                  = '1';
	const TRANSACTIOANL_SNEDTYPE_DESIGN_VIA_CONNECTOR           = '2';



	/**
	 * Get the api enabled.
	 *
	 * @return mixed
	 */
    public function isEnabled()
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_TRANSACTIONAL_API_ENABLED);
    }

    /**
	 * Get api username.
	 *
	 * @param mixed $website
	 *
	 * @return mixed
	 * @throws Mage_Core_Exception
	 */
    public function getApiUsername($website = 0)
    {
        $website = Mage::app()->getWebsite($website);

        return $website->getConfig(self::XML_PATH_TRANSACTIONAL_API_USERNAME);
    }

    /**
	 * Get api password.
	 *
	 * @param mixed $website
	 *
	 * @return mixed
	 * @throws Mage_Core_Exception
	 */
    public function getApiPassword($website = 0)
    {
        $website = Mage::app()->getWebsite($website);

        return $website->getConfig(self::XML_PATH_TRANSACTIONAL_API_PASSWORD);
    }

    /**
	 * Website by name.
	 * @param $websiteName
	 *
	 * @return Varien_Object
	 */
    public function getWebsiteByName($websiteName)
    {
        $website = Mage::getModel('core/website')->getCollection()
            ->addFieldToFilter('code', $websiteName)->getFirstItem();

        return $website;
    }

    /**
     * Find template lable by id.
     * @param mixed $templateId
     *
     * @return string
     */
    public function getTemplateLabelById($templateId)
    {
        $default = Mage::getModel('email_connector/adminhtml_source_transactional_defaultselect')->toOptionArray();
        $custom = Mage::getModel('email_connector/adminhtml_source_transactional_customselect')->toOptionArray();
        $all = array_merge($default,$custom);

        $label = "";
        foreach($all as $one){
            if($one['value'] == $templateId) {
                $label = $one['label'];
                break;
            }
        }
        return $label;
    }

    /**
     * get all templates mapping
     * @param $storeId
     * @return array
     */
	public function getAllTemplateMapping($storeId){
		$allTemplateMapping = array();

		$defaultTemplateMapping = $this->getDefaultEmailTemplates($storeId);
		$customTemplateMapping = $this->getCustomEmailTemplates($storeId);

		if(is_array($defaultTemplateMapping) && is_array($customTemplateMapping))
		{
			$allTemplateMapping = array_merge($defaultTemplateMapping, $customTemplateMapping);
		}
		elseif(is_array($defaultTemplateMapping))
		{
			$allTemplateMapping = $defaultTemplateMapping;
		}
		elseif(is_array($customTemplateMapping))
		{
			$allTemplateMapping = $customTemplateMapping;
		}

		return $allTemplateMapping;
	}

    /**
     * Transactional emails check for campaign id if it's mapped.
     * Default Emails stored in magento
     *
     * @param $templateId
     * @param $key
     * @param null $storeId
     * @return bool|mixed
     */
	public function getMapping($templateId, $key ,$storeId = null)
	{
		$allTemplateMapping = $this->getAllTemplateMapping($storeId);
		$isMapped = false;

		foreach($allTemplateMapping as $templateMapping)
		{
			if($isMapped = $this->findTemplateIdInArray($templateId, $templateMapping))
				break;
		}

		if(is_array($isMapped) && $key == self::MAP_COLUMN_KEY_DATAFIELD)
			return $isMapped[self::MAP_COLUMN_KEY_DATAFIELD];

		if(is_array($isMapped) && $key == self::MAP_COLUMN_KEY_SENDTYPE)
			return $isMapped[self::MAP_COLUMN_KEY_SENDTYPE];

        if(is_array($isMapped) && $key == self::MAP_COLUMN_KEY_FROM_ADDRESS)
            return $isMapped[self::MAP_COLUMN_KEY_FROM_ADDRESS];

        if(is_array($isMapped) && $key == self::MAP_COLUMN_KEY_ATTACHMENT_ID)
            return $isMapped[self::MAP_COLUMN_KEY_ATTACHMENT_ID];

		return $isMapped;
	}

    /**
     *  find template id in array
     * @param mixed $id
     * @param array $data
     *
     * @return mixed
     */
    public function findTemplateIdInArray($id, $data)
    {
        $result = false;
        foreach($data as $key => $value){
            if($key == 'template' && $value == $id) {
                $result = $data;
                break;
            }
        }
        return $result;
    }

    /**
     * get un-serialised config value for all default email templates for all modules
     *
     * @return array
     */
    public function getDefaultEmailTemplates($storeId = null)
    {
        return unserialize(Mage::getStoreConfig(self::XML_PATH_CONNECTOR_TRANSACTIONAL_EMAIL_DEFAULT, $storeId));
    }

    /**
     * get un-serialised config value for custom templates
     *
     * @return array
     */
    public function getCustomEmailTemplates($storeId = null)
    {
        return unserialize(Mage::getStoreConfig(self::XML_PATH_CONNECTOR_TRANSACTIONAL_EMAIL_CUSTOM, $storeId));
    }

    /**
     * Get the contact id for the custoemer based on website id.
     * @param $email
     * @param $websiteId
     * @return string contact_id
     */
    public function getContactId($email, $websiteId)
    {
        $client = $this->getWebsiteApiClient($websiteId);
        $response = $client->postContacts($email);
        if (isset($response->message))
            return $response->message;
        return $response->id;
    }

    /**
	 * Update contact default datafields.
	 *
	 * @param $contacData
	 */
    public function updateContactData($contacData)
    {
        $client = $this->getWebsiteApiClient($contacData->getWebsite());
        $email  = $contacData->getCustomerEmail();
        /**
         * Transactional account data default datafields.
         */
        $data = array(
            array(
                'Key' => 'LAST_ORDER_ID',
                'Value' => $contacData->getOrderId()),
            array(
                'Key' => 'CUSTOMER_ID',
                'Value' => $contacData->getCustomerId()),
            array(
                'Key' => 'ORDER_INCREMENT_ID',
                'Value' => $contacData->getOrderIncrementId()),
            array(
                'Key' => 'WEBSITE_NAME',
                'Value' => $contacData->getWebsiteName()),
            array(
                'Key' => 'STORE_NAME',
                'Value' => $contacData->getStoreName()),
            array(
                'Key' => 'LAST_ORDER_DATE',
                'Value' => $contacData->getOrderDate())
        );
        $client->updateContactDatafieldsByEmail($email, $data);
    }

    /**
     * Api client by website.
     * @param int $website
     * @return Dotdigitalgroup_Email_Model_Apiconnector_Client
     */
    public function getWebsiteApiClient($website = 0)
    {
        $client = Mage::getModel('email_connector/apiconnector_client');
	    $website = Mage::app()->getWebsite($website);
	    if ($website) {
		    $client->setApiUsername($this->getApiUsername($website))
		           ->setApiPassword($this->getApiPassword($website));
	    }
	    return $client;
    }

    public function getEmailSettings($path, $websiteId)
    {
        $helper = Mage::helper('connector');
        return $helper->getWebsiteConfig($path, $websiteId);
    }

    public function getFromAddress($websiteId){
        return $this->getEmailSettings(self::XML_PATH_CONNECTOR_TRANSACTIONAL_FROM_ADDRESS, $websiteId);
    }

    public function getReplyAction($websiteId){
        return $this->getEmailSettings(self::XML_PATH_CONNECTOR_TRANSACTIONAL_REPLY_ACTION, $websiteId);
    }

    public function getReplyAddress($websiteId){
        return $this->getEmailSettings(self::XML_PATH_CONNECTOR_TRANSACTIONAL_REPLY_ADDRESS, $websiteId);
    }

    public function getSendCopy($websiteId){
        return $this->getEmailSettings(self::XML_PATH_CONNECTOR_TRANSACTIONAL_SEND_COPY, $websiteId);
    }
    public function getUnsubscribeLink($websiteId){
        return $this->getEmailSettings(self::XML_PATH_CONNECTOR_TRANSACTIONAL_UNSUBSCRIBE_LINK, $websiteId);
    }
}