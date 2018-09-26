<?php

class Dotdigitalgroup_Email_Model_Template extends Mage_Core_Model_Abstract
{
    /**
     * HTML template type.
     */
    const TEMPLATE_TYPE = 1;

    const XML_PATH_WISHLIST_EMAIL_EMAIL_TEMPLATE = 'wishlist/email/email_template';

    const XML_PATH_DDG_TEMPLATE_NEW_ACCCOUNT =
        'dotmailer_email_templates/email_templates/customer_create_account_email_template';
    const XML_PATH_DDG_TEMPLATE_NEW_ACCCOUNT_CONFIRMATION_KEY =
        'dotmailer_email_templates/email_templates/customer_create_account_email_confirmation_template';
    const XML_PATH_DDG_TEMPLATE_NEW_ACCOUNT_CONFIRMATION =
        'dotmailer_email_templates/email_templates/customer_create_account_email_confirmed_template';
    const XML_PATH_DDG_TEMPLATE_FORGOT_PASSWORD =
        'dotmailer_email_templates/email_templates/customer_password_forgot_email_template';
    const XML_PATH_DDG_TEMPLATE_REMIND_PASSWORD =
        'dotmailer_email_templates/email_templates/customer_password_remind_email_template';
    const XML_PATH_DDG_TEMPLATE_WISHLIST_PRODUCT_SHARE =
        'dotmailer_email_templates/email_templates/wishlist_email_email_template';
    const XML_PATH_DDG_TEMPLATE_FORGOT_ADMIN_PASSWORD =
        'dotmailer_email_templates/email_templates/admin_emails_forgot_email_template';
    const XML_PATH_DDG_TEMPLATE_SUBSCRIPTION_SUCCESS =
        'dotmailer_email_templates/email_templates/newsletter_subscription_success_email_template';
    const XML_PATH_DDG_TEMPLATE_SUBSCRIPTION_CONFIRMATION =
        'dotmailer_email_templates/email_templates/newsletter_subscription_confirm_email_template';
    const XML_PATH_DGG_TEMPLATE_NEW_ORDER_CONFIRMATION =
        'dotmailer_email_templates/email_templates/sales_email_order_template';
    const XML_PATH_DDG_TEMPLATE_NEW_ORDER_CONFIRMATION_GUEST =
        'dotmailer_email_templates/email_templates/sales_email_order_guest_template';
    const XML_PATH_DDG_TEMPLATE_ORDER_UPDATE =
        'dotmailer_email_templates/email_templates/sales_email_order_comment_template';
    const XML_PATH_DDG_TEMPLATE_ORDER_UPDATE_GUEST =
        'dotmailer_email_templates/email_templates/sales_email_order_comment_guest_template';
    const XML_PATH_DDG_TEMPLATE_NEW_SHIPMENT =
        'dotmailer_email_templates/email_templates/sales_email_shipment_template';
    const XML_PATH_DDG_TEMPLATE_NEW_SHIPMENT_GUEST =
        'dotmailer_email_templates/email_templates/sales_email_shipment_guest_template';
    const XML_PATH_DDG_TEMPLATE_INVOICE_UPDATE =
        'dotmailer_email_templates/email_templates/sales_email_invoice_comment_template';
    const XML_PATH_DDG_TEMPLATE_UNSUBSCRIBE_SUCCESS =
        'dotmailer_email_templates/email_templates/newsletter_subscription_un_email_template';
    const XML_PATH_DDG_TEMPLATE_INVOICE_UPDATE_GUEST =
        'dotmailer_email_templates/email_templates/sales_email_invoice_comment_guest_template';
    const XML_PATH_DDG_TEMPLATE_NEW_INVOICE =
        'dotmailer_email_templates/email_templates/sales_email_invoice_template';
    const XML_PATH_DDG_TEMPLATE_NEW_INVOICE_GUEST =
        'dotmailer_email_templates/email_templates/sales_email_invoice_guest_template';
    const XML_PATH_DDG_TEMPLATE_NEW_CREDIT_MEMO =
        'dotmailer_email_templates/email_templates/sales_email_creditmemo_template';
    const XML_PATH_DDG_TEMPLATE_NEW_CREDIT_MEMO_GUEST =
        'dotmailer_email_templates/email_templates/sales_email_creditmemo_guest_template';
    const XML_PATH_DDG_TEMPLATE_CREDIT_MEMO_UPDATE =
        'dotmailer_email_templates/email_templates/sales_email_creditmemo_comment_template';
    const XML_PATH_DDG_TEMPLATE_SHIPMENT_UPDATE =
        'dotmailer_email_templates/email_templates/sales_email_shipment_comment_template';
    const XML_PATH_DDG_TEMPLATE_SHIPMENT_UPDATE_GUEST =
        'dotmailer_email_templates/email_templates/sales_email_shipment_comment_guest_template';
    const XML_PATH_DDG_TEMPLATE_CONTACT_FORM =
        'dotmailer_email_templates/email_templates/contact_email_email_template';
    const XML_PATH_DDG_TEMPLATE_CREDIT_MEMO_UPDATE_GUEST =
        'dotmailer_email_templates/email_templates/sales_email_creditmemo_comment_guest_template';
    const XML_PATH_DDG_TEMPLATE_SEND_PRODUCT_TO_FRIEND =
        'dotmailer_email_templates/email_templates/sendfriend_email_template';
    const XML_PATH_DDG_TEMPLATE_PRODUCT_STOCK_ALERT =
        'dotmailer_email_templates/email_templates/product_stock_alert_template';
    const XML_PATH_DDG_TEMPLATE_PRODUCT_PRICE_ALERT =
        'dotmailer_email_templates/email_templates/product_price_alert_template';

    /**
     * Mapping from template code = config path for templates.
     * @var array
     */
    public $templateConfigMapping = [
        'customer_create_account_email_template' =>
            Mage_Customer_Model_Customer::XML_PATH_REGISTER_EMAIL_TEMPLATE,
        'customer_create_account_email_confirmed_template' =>
            Mage_Customer_Model_Customer::XML_PATH_CONFIRMED_EMAIL_TEMPLATE,
        'customer_create_account_email_confirmation_template' =>
            Mage_Customer_Model_Customer::XML_PATH_CONFIRM_EMAIL_TEMPLATE,
        'customer_password_forgot_email_template' =>
            Mage_Customer_Model_Customer::XML_PATH_FORGOT_EMAIL_TEMPLATE,
        'customer_password_remind_email_template' =>
            Mage_Customer_Model_Customer::XML_PATH_REMIND_EMAIL_TEMPLATE,
        'wishlist_email_email_template' => self::XML_PATH_WISHLIST_EMAIL_EMAIL_TEMPLATE,
        'admin_emails_forgot_email_template' => Mage_Admin_Model_User::XML_PATH_FORGOT_EMAIL_TEMPLATE,
        'newsletter_subscription_success_email_template' =>
            Mage_Newsletter_Model_Subscriber::XML_PATH_SUCCESS_EMAIL_TEMPLATE,
        'newsletter_subscription_confirm_email_template' =>
            Mage_Newsletter_Model_Subscriber::XML_PATH_CONFIRM_EMAIL_TEMPLATE,
        'newsletter_subscription_un_email_template' =>
            Mage_Newsletter_Model_Subscriber::XML_PATH_UNSUBSCRIBE_EMAIL_TEMPLATE,
        'sales_email_order_template' =>
            Mage_Sales_Model_Order::XML_PATH_EMAIL_TEMPLATE,
        'sales_email_order_guest_template' =>
            Mage_Sales_Model_Order::XML_PATH_EMAIL_GUEST_TEMPLATE,
        'sales_email_order_comment_template' =>
            Mage_Sales_Model_Order::XML_PATH_UPDATE_EMAIL_TEMPLATE,
        'sales_email_order_comment_guest_template' =>
            Mage_Sales_Model_Order::XML_PATH_UPDATE_EMAIL_GUEST_TEMPLATE,
        'sales_email_shipment_template' =>
            Mage_Sales_Model_Order_Shipment::XML_PATH_EMAIL_TEMPLATE,
        'sales_email_shipment_guest_template' =>
            Mage_Sales_Model_Order_Shipment::XML_PATH_EMAIL_GUEST_TEMPLATE,
        'sales_email_shipment_comment_template' =>
            Mage_Sales_Model_Order_Shipment::XML_PATH_UPDATE_EMAIL_TEMPLATE,
        'sales_email_shipment_comment_guest_template' =>
            Mage_Sales_Model_Order_Shipment::XML_PATH_UPDATE_EMAIL_GUEST_TEMPLATE,
        'sales_email_invoice_template' =>
            Mage_Sales_Model_Order_Invoice::XML_PATH_EMAIL_TEMPLATE,
        'sales_email_invoice_guest_template' =>
            Mage_Sales_Model_Order_Invoice::XML_PATH_EMAIL_GUEST_TEMPLATE,
        'sales_email_invoice_comment_template' =>
            Mage_Sales_Model_Order_Invoice::XML_PATH_UPDATE_EMAIL_TEMPLATE,
        'sales_email_invoice_comment_guest_template' =>
            Mage_Sales_Model_Order_Invoice::XML_PATH_UPDATE_EMAIL_GUEST_TEMPLATE,
        'sales_email_creditmemo_template' =>
            Mage_Sales_Model_Order_Creditmemo::XML_PATH_EMAIL_TEMPLATE,
        'sales_email_creditmemo_guest_template' =>
            Mage_Sales_Model_Order_Creditmemo::XML_PATH_EMAIL_GUEST_TEMPLATE,
        'sales_email_creditmemo_comment_template' =>
            Mage_Sales_Model_Order_Creditmemo::XML_PATH_UPDATE_EMAIL_TEMPLATE,
        'sales_email_creditmemo_comment_guest_template' =>
            Mage_Sales_Model_Order_Creditmemo::XML_PATH_UPDATE_EMAIL_GUEST_TEMPLATE,
        'sendfriend_email_template' => Mage_Sendfriend_Helper_Data::XML_PATH_EMAIL_TEMPLATE,
        'product_stock_alert_template' => Mage_ProductAlert_Model_Email::XML_PATH_EMAIL_STOCK_TEMPLATE,
        'product_price_alert_template' => Mage_ProductAlert_Model_Email::XML_PATH_EMAIL_PRICE_TEMPLATE,
    ];

    /**
     * Mapping for template code = dotmailer path templates.
     *
     * @var array
     */
    public $templateConfigIdToDotmailerConfigPath = [
        'customer_create_account_email_template' => self::XML_PATH_DDG_TEMPLATE_NEW_ACCCOUNT,
        'customer_create_account_email_confirmation_template' =>
            self::XML_PATH_DDG_TEMPLATE_NEW_ACCCOUNT_CONFIRMATION_KEY,
        'customer_create_account_email_confirmed_template' => self::XML_PATH_DDG_TEMPLATE_NEW_ACCOUNT_CONFIRMATION,
        'customer_password_forgot_email_template' => self::XML_PATH_DDG_TEMPLATE_FORGOT_PASSWORD,
        'customer_password_remind_email_template' => self::XML_PATH_DDG_TEMPLATE_REMIND_PASSWORD,
        'admin_emails_forgot_email_template' => self::XML_PATH_DDG_TEMPLATE_FORGOT_ADMIN_PASSWORD,
        'newsletter_subscription_success_email_template' => self::XML_PATH_DDG_TEMPLATE_SUBSCRIPTION_SUCCESS,
        'newsletter_subscription_confirm_email_template' => self::XML_PATH_DDG_TEMPLATE_SUBSCRIPTION_CONFIRMATION,
        'newsletter_subscription_un_email_template' => self::XML_PATH_DDG_TEMPLATE_UNSUBSCRIBE_SUCCESS,
        'sales_email_order_template' => self::XML_PATH_DGG_TEMPLATE_NEW_ORDER_CONFIRMATION,
        'sales_email_order_guest_template' => self::XML_PATH_DDG_TEMPLATE_NEW_ORDER_CONFIRMATION_GUEST,
        'sales_email_order_comment_template' => self::XML_PATH_DDG_TEMPLATE_ORDER_UPDATE,
        'sales_email_order_comment_guest_template' => self::XML_PATH_DDG_TEMPLATE_ORDER_UPDATE_GUEST,
        'sales_email_shipment_template' => self::XML_PATH_DDG_TEMPLATE_NEW_SHIPMENT,
        'sales_email_shipment_guest_template' => self::XML_PATH_DDG_TEMPLATE_NEW_SHIPMENT_GUEST,
        'sales_email_invoice_comment_template' => self::XML_PATH_DDG_TEMPLATE_INVOICE_UPDATE,
        'sales_email_invoice_comment_guest_template' => self::XML_PATH_DDG_TEMPLATE_INVOICE_UPDATE_GUEST,
        'sales_email_invoice_template' => self::XML_PATH_DDG_TEMPLATE_NEW_INVOICE,
        'sales_email_invoice_guest_template' => self::XML_PATH_DDG_TEMPLATE_NEW_INVOICE_GUEST,
        'sales_email_creditmemo_template' => self::XML_PATH_DDG_TEMPLATE_NEW_CREDIT_MEMO,
        'sales_email_creditmemo_guest_template' => self::XML_PATH_DDG_TEMPLATE_NEW_CREDIT_MEMO_GUEST,
        'sales_email_creditmemo_comment_template' => self::XML_PATH_DDG_TEMPLATE_CREDIT_MEMO_UPDATE,
        'sales_email_creditmemo_comment_guest_template' => self::XML_PATH_DDG_TEMPLATE_CREDIT_MEMO_UPDATE_GUEST,
        'sales_email_shipment_comment_template' => self::XML_PATH_DDG_TEMPLATE_SHIPMENT_UPDATE,
        'sales_email_shipment_comment_guest_template' => self::XML_PATH_DDG_TEMPLATE_SHIPMENT_UPDATE_GUEST,
        'sendfriend_email_template' => self::XML_PATH_DDG_TEMPLATE_SEND_PRODUCT_TO_FRIEND,
        'wishlist_email_email_template' => self::XML_PATH_DDG_TEMPLATE_WISHLIST_PRODUCT_SHARE,
        'product_stock_alert_template' => self::XML_PATH_DDG_TEMPLATE_PRODUCT_STOCK_ALERT,
        'product_price_alert_template' => self::XML_PATH_DDG_TEMPLATE_PRODUCT_PRICE_ALERT
    ];

    /**
     * @var array
     */
    public $proccessedCampaings;

    /**
     * Load email_template by code/name.
     *
     * @param $templateCode
     * @return mixed
     */
    public function loadByTemplateCode($templateCode)
    {
        //Mage_Core_Model_Resource_Email_Template_Collection
        $templateCollection = Mage::getModel('core/resource_email_template_collection');
        $template = $templateCollection
            ->addFieldToFilter('template_code', $templateCode)
            ->setPageSize(1);

        return $template->getFirstItem();
    }

    /**
     * Delete email_template.
     *
     * @param $templatecode
     */
    public function deleteTemplateByCode($templatecode)
    {
        $template = $this->loadByTemplateCode($templatecode);

        if ($template->getId()) {
            $template->delete();
        }
    }

    /**
     * Template sync.
     *
     * @return array
     */
    public function sync()
    {
        $result = ['store' => 'Stores : ', 'message' => 'Done.'];
        $lastWebsiteId = '0';
        $helper = Mage::helper('ddg');
        /** @var Mage_Core_Model_Store $store */
        foreach (Mage::app()->getStores(true) as $store) {
            //store not enabled to sync
            if (! $helper->isStoreEnabled($store)) {
                continue;
            }
            //reset the campaign ids for each website
            $websiteId = $store->getWebsiteId();
            if ($websiteId != $lastWebsiteId) {
                $this->proccessedCampaings = [];
                $lastWebsiteId = $websiteId;
            }
            foreach($this->templateConfigIdToDotmailerConfigPath as $configTemplateId => $dotConfigPath) {

                $campaignId = $store->getConfig($dotConfigPath);
                $configPath = $this->templateConfigMapping[$configTemplateId];
                $emailTemplateId = $store->getConfig($configPath);

                if ($campaignId && $emailTemplateId && ! in_array($campaignId, $this->proccessedCampaings)) {
                    $this->syncEmailTemplate($campaignId, $emailTemplateId, $store);
                    $result['store'] .= ', ' . $store->getCode();
                    $this->proccessedCampaings[$campaignId] = $campaignId;
                }
            }
        }

        return $result;
    }

    /**
     * @param $campaignId
     * @param $emailTemplateId
     * @param $store
     * @return mixed
     */
    private function syncEmailTemplate($campaignId, $emailTemplateId, $store)
    {
        $websiteId = $store->getWebsiteId();
        $helper = Mage::helper('ddg');
        $client = $helper->getWebsiteApiClient($websiteId);
        $dmCampaign = $client->getCampaignByIdWithPreparedContent($campaignId);

        if (isset($dmCampaign->message)) {
            $message = $dmCampaign->message;
            $helper->log($message);
            return $message;
        }

        $this->updateTemplateById($dmCampaign, $campaignId, $emailTemplateId);
    }

    /**
     * @param $template
     * @param $dmCampaign
     * @param $campaignId
     * @param string $origTemplateCode
     * @return mixed
     */
    public function saveTemplate($template, $dmCampaign, $campaignId, $origTemplateCode = '')
    {
        $templateName = $dmCampaign->name . '_' . $campaignId;

        try {
            $template->setTemplateCode($templateName)
                ->setOrigTemplateCode($origTemplateCode)
                ->setTemplateSubject(utf8_encode($dmCampaign->subject))
                ->setTemplateText($dmCampaign->processedHtmlContent)
                ->setTemplateType(Mage_Core_Model_Template::TYPE_HTML)
                ->setTemplateSenderName($dmCampaign->fromName)
                ->setTemplateSenderEmail($dmCampaign->fromAddress->email);

            $template->save();
        } catch (\Exception $e) {
            $this->helper->log($e->getMessage());
        }

        return $template;
    }

    /**
     * @param $dmCampaign
     * @param $campaignId
     * @param $templateId
     * @param string $origTemplateCode
     * @return Mage_Core_Model_Abstract
     */
    private function updateTemplateById($dmCampaign, $campaignId, $templateId, $origTemplateCode = '')
    {
        //dotmailer template name (campaign name _ campaign id)
        $templateName = $dmCampaign->name . '_' . $campaignId;

        try {
            $template = Mage::getModel('core/email_template')
                ->load($templateId);
            $template->setOrigTemplateCode($origTemplateCode)
                ->setTemplateCode($templateName)
                ->setTemplateSenderName($dmCampaign->fromName)
                ->setTemplateText($dmCampaign->processedHtmlContent)
                ->setTemplateType(Mage_Core_Model_Template::TYPE_HTML)
                ->setTemplateSubject(utf8_encode($dmCampaign->subject))
                ->setTemplateSenderEmail($dmCampaign->fromAddress->email);

            $template->save();
        } catch (\Exception $e) {
            Mage::helper('ddg')->log($e->getMessage());
        }

        return $template;
    }

    /**
     * @param $templateConfigPath
     * @param $campaignId
     * @param $scope
     * @param $scopeId
     * @return bool|mixed
     */
    public function saveTemplateWithConfigPath($templateConfigPath, $campaignId, $scope, $scopeId)
    {
        $helper = Mage::helper('ddg');
        if ($scope == Mage_Adminhtml_Block_System_Config_Form::SCOPE_WEBSITES) {
            $websiteId = $scopeId;
        } elseif ($scope == Mage_Adminhtml_Block_System_Config_Form::SCOPE_STORES) {
            $websiteId = Mage::app()->getWebsite($scopeId)->getId();
        } else {
            $websiteId = '0';
        }

        //get the campaign from api
        $client = $helper->getWebsiteApiClient($websiteId);
        $dmCampaign = $client->getCampaignByIdWithPreparedContent($campaignId);
        if (isset($dmCampaign->message)) {
            $helper->log('Failed to get api template : ' . $dmCampaign->message);
            return false;
        }

        $templateName = $dmCampaign->name . '_' . $campaignId;
        $template = $this->loadByTemplateCode($templateName);

        return $this->saveTemplate($template, $dmCampaign, $campaignId, $templateConfigPath);
    }
}