<?php

class Dotdigitalgroup_Email_Helper_Config
{
    const MODULE_NAME                                       = 'Dotdigitalgroup_Email';

    /**
     * API SECTION.
     */
    //API settings
    const XML_PATH_CONNECTOR_API_ENABLED                    = 'connector_api_credentials/api/enabled';
    const XML_PATH_CONNECTOR_API_USERNAME                   = 'connector_api_credentials/api/username';
    const XML_PATH_CONNECTOR_API_PASSWORD                   = 'connector_api_credentials/api/password';
    const XML_PATH_CONNECTOR_CLIENT_ID                      = 'connector_api_credentials/oauth/client_id';
    const XML_PATH_CONNECTOR_CLIENT_SECRET_ID               = 'connector_api_credentials/oauth/client_key';

    /**
     * SMS SECTION.
     */
    //enabled
    const XML_PATH_CONNECTOR_SMS_ENABLED_1                  = 'connector_sms/sms_one/enabled';
    const XML_PATH_CONNECTOR_SMS_ENABLED_2                  = 'connector_sms/sms_two/enabled';
    const XML_PATH_CONNECTOR_SMS_ENABLED_3                  = 'connector_sms/sms_three/enabled';
    const XML_PATH_CONNECTOR_SMS_ENABLED_4                  = 'connector_sms/sms_four/enabled';
    //status
    const XML_PATH_CONNECTOR_SMS_STATUS_1                   = 'connector_sms/sms_one/status';
    const XML_PATH_CONNECTOR_SMS_STATUS_2                   = 'connector_sms/sms_two/status';
    const XML_PATH_CONNECTOR_SMS_STATUS_3                   = 'connector_sms/sms_three/status';
    const XML_PATH_CONNECTOR_SMS_STATUS_4                   = 'connector_sms/sms_four/status';
    //message
    const XML_PATH_CONNECTOR_SMS_MESSAGE_1                  = 'connector_sms/sms_one/message';
    const XML_PATH_CONNECTOR_SMS_MESSAGE_2                  = 'connector_sms/sms_two/message';
    const XML_PATH_CONNECTOR_SMS_MESSAGE_3                  = 'connector_sms/sms_three/message';
    const XML_PATH_CONNECTOR_SMS_MESSAGE_4                  = 'connector_sms/sms_four/message';

    /**
     * SYNC SECTION.
     */
    const XML_PATH_CONNECTOR_SYNC_CONTACT_ENABLED           = 'connector_sync_settings/sync/contact_enabled';
    const XML_PATH_CONNECTOR_SYNC_SUBSCRIBER_ENABLED        = 'connector_sync_settings/sync/subscriber_enabled';
    const XML_PATH_CONNECTOR_SYNC_ORDER_ENABLED             = 'connector_sync_settings/sync/order_enabled';
    const XML_PATH_CONNECTOR_SYNC_WISHLIST_ENABLED          = 'connector_sync_settings/sync/wishlist_enabled';
    const XML_PATH_CONNECTOR_SYNC_REVIEW_ENABLED            = 'connector_sync_settings/sync/review_enabled';
    const XML_PATH_CONNECTOR_SYNC_QUOTE_ENABLED             = 'connector_sync_settings/sync/quote_enabled';

    const XML_PATH_CONNECTOR_CUSTOMERS_ADDRESS_BOOK_ID      = 'connector_sync_settings/address_book/customers';
    const XML_PATH_CONNECTOR_SUBSCRIBERS_ADDRESS_BOOK_ID    = 'connector_sync_settings/address_book/subscribers';
    const XML_PATH_CONNECTOR_GUEST_ADDRESS_BOOK_ID          = 'connector_sync_settings/address_book/guests';
    // Mapping
    const XML_PATH_CONNECTOR_MAPPING_LAST_ORDER_ID          = 'connector_data_mapping/customer_data/last_order_id';
    const XML_PATH_CONNECTOR_MAPPING_LAST_QUOTE_ID          = 'connector_data_mapping/customer_data/last_quote_id';
    const XML_PATH_CONNECTOR_MAPPING_CUSTOMER_ID            = 'connector_data_mapping/customer_data/customer_id';
    const XML_PATH_CONNECTOR_MAPPING_CUSTOM_DATAFIELDS      = 'connector_data_mapping/customer_data/custom_attributes';
    const XML_PATH_CONNECTOR_MAPPING_CUSTOMER_STORENAME     = 'connector_data_mapping/customer_data/store_name';
    const XML_PATH_CONNECTOR_MAPPING_CUSTOMER_TOTALREFUND   = 'connector_data_mapping/customer_data/total_refund';
    const XML_PATH_CONNECTOR_MAPPING_SWEETTOOTH_ACTIVE      = 'connector_data_mapping/sweet_tooth/active';

    // Address Book Pref
    const XML_PATH_CONNECTOR_ADDRESSBOOK_PREF_CAN_CHANGE_BOOKS  = 'connector_sync_settings/address_book_pref/can_change';
    const XML_PATH_CONNECTOR_ADDRESSBOOK_PREF_SHOW_BOOKS        = 'connector_sync_settings/address_book_pref/show_books';
    const XML_PATH_CONNECTOR_ADDRESSBOOK_PREF_CAN_SHOW_FIELDS   = 'connector_sync_settings/address_book_pref/can_show_fields';
    const XML_PATH_CONNECTOR_ADDRESSBOOK_PREF_SHOW_FIELDS       = 'connector_sync_settings/address_book_pref/fields_to_show';

    /**
     * Abandoned Carts.
     */
    const XML_PATH_CONNECTOR_CUSTOMER_ABANDONED_CARTS_ENABLED_1 = 'connector_lost_baskets/customers/enabled_1';
    const XML_PATH_CONNECTOR_CUSTOMER_ABANDONED_CAMPAIGN_1      = 'connector_lost_baskets/customers/campaign_1';
    const XML_PATH_CONNECTOR_CUSTOMER_ABANDONED_CARTS_ENABLED_2 = 'connector_lost_baskets/customers/enabled_2';
    const XML_PATH_CONNECTOR_CUSTOMER_ABANDONED_CAMPAIGN_2      = 'connector_lost_baskets/customers/campaign_2';
    const XML_PATH_CONNECTOR_CUSTOMER_ABANDONED_CARTS_ENABLED_3 = 'connector_lost_baskets/customers/enabled_3';
    const XML_PATH_CONNECTOR_CUSTOMER_ABANDONED_CAMPAIGN_3      = 'connector_lost_baskets/customers/campaign_3';
    const XML_PATH_CONNECTOR_GUEST_ABANDONED_CARTS_ENABLED_1    = 'connector_lost_baskets/guests/enabled_1';
    const XML_PATH_CONNECTOR_GUEST_ABANDONED_CAMPAIGN_1         = 'connector_lost_baskets/guests/campaign_1';
    const XML_PATH_CONNECTOR_GUEST_ABANDONED_CARTS_ENABLED_2    = 'connector_lost_baskets/guests/enabled_2';
    const XML_PATH_CONNECTOR_GUEST_ABANDONED_CAMPAIGN_2         = 'connector_lost_baskets/guests/campaign_2';
    const XML_PATH_CONNECTOR_GUEST_ABANDONED_CARTS_ENABLED_3    = 'connector_lost_baskets/guests/enabled_3';
    const XML_PATH_CONNECTOR_GUEST_ABANDONED_CAMPAIGN_3         = 'connector_lost_baskets/guests/campaign_3';

    /**
     * Datafields Mapping.
     */
    const XML_PATH_CONNECTOR_CUSTOMER_ID                    = 'connector_data_mapping/customer_data/customer_id';
    const XML_PATH_CONNECTOR_CUSTOMER_FIRSTNAME             = 'connector_data_mapping/customer_data/firstname';
    const XML_PATH_CONNECTOR_CUSTOMER_LASTNAME              = 'connector_data_mapping/customer_data/lastname';
    const XML_PATH_CONNECTOR_CUSTOMER_DOB                   = 'connector_data_mapping/customer_data/dob';
    const XML_PATH_CONNECTOR_CUSTOMER_GENDER                = 'connector_data_mapping/customer_data/gender';
    const XML_PATH_CONNECTOR_CUSTOMER_WEBSITE_NAME          = 'connector_data_mapping/customer_data/website_name';
    const XML_PATH_CONNECTOR_CUSTOMER_STORE_NAME            = 'connector_data_mapping/customer_data/store_name';
    const XML_PATH_CONNECTOR_CUSTOMER_CREATED_AT            = 'connector_data_mapping/customer_data/created_at';
    const XML_PATH_CONNECTOR_CUSTOMER_LAST_LOGGED_DATE      = 'connector_data_mapping/customer_data/last_logged_date';
    const XML_PATH_CONNECTOR_CUSTOMER_CUSTOMER_GROUP        = 'connector_data_mapping/customer_data/customer_group';
    const XML_PATH_CONNECTOR_CUSTOMER_REVIEW_COUNT          = 'connector_data_mapping/customer_data/review_count';
    const XML_PATH_CONNECTOR_CUSTOMER_LAST_REVIEW_DATE      = 'connector_data_mapping/customer_data/last_review_date';
    const XML_PATH_CONNECTOR_CUSTOMER_BILLING_ADDRESS_1     = 'connector_data_mapping/customer_data/billing_address_1';
    const XML_PATH_CONNECTOR_CUSTOMER_BILLING_ADDRESS_2     = 'connector_data_mapping/customer_data/billing_address_2';
    const XML_PATH_CONNECTOR_CUSTOMER_BILLING_CITY          = 'connector_data_mapping/customer_data/billing_city';
    const XML_PATH_CONNECTOR_CUSTOMER_BILLING_STATE         = 'connector_data_mapping/customer_data/billing_state';
    const XML_PATH_CONNECTOR_CUSTOMER_BILLING_COUNTRY       = 'connector_data_mapping/customer_data/billing_country';
    const XML_PATH_CONNECTOR_CUSTOMER_BILLING_POSTCODE      = 'connector_data_mapping/customer_data/billing_postcode';
    const XML_PATH_CONNECTOR_CUSTOMER_BILLING_TELEPHONE     = 'connector_data_mapping/customer_data/billing_telephone';
    const XML_PATH_CONNECTOR_CUSTOMER_DELIVERY_ADDRESS_1    = 'connector_data_mapping/customer_data/delivery_address_1';
    const XML_PATH_CONNECTOR_CUSTOMER_DELIVERY_ADDRESS_2    = 'connector_data_mapping/customer_data/delivery_address_2';
    const XML_PATH_CONNECTOR_CUSTOMER_DELIVERY_CITY         = 'connector_data_mapping/customer_data/delivery_city';
    const XML_PATH_CONNECTOR_CUSTOMER_DELIVERY_STATE        = 'connector_data_mapping/customer_data/delivery_state';
    const XML_PATH_CONNECTOR_CUSTOMER_DELIVERY_COUNTRY      = 'connector_data_mapping/customer_data/delivery_country';
    const XML_PATH_CONNECTOR_CUSTOMER_DELIVERY_POSTCODE     = 'connector_data_mapping/customer_data/delivery_postcode';
    const XML_PATH_CONNECTOR_CUSTOMER_DELIVERY_TELEPHONE    = 'connector_data_mapping/customer_data/delivery_telephone';
    const XML_PATH_CONNECTOR_CUSTOMER_TOTAL_NUMBER_ORDER    = 'connector_data_mapping/customer_data/number_of_orders';
    const XML_PATH_CONNECTOR_CUSTOMER_AOV                   = 'connector_data_mapping/customer_data/average_order_value';
    const XML_PATH_CONNECTOR_CUSTOMER_TOTAL_SPEND           = 'connector_data_mapping/customer_data/total_spend';
    const XML_PATH_CONNECTOR_CUSTOMER_LAST_ORDER_DATE       = 'connector_data_mapping/customer_data/last_order_date';
    const XML_PATH_CONNECTOR_CUSTOMER_LAST_ORDER_ID         = 'connector_data_mapping/customer_data/last_order_id';
    const XML_PATH_CONNECTOR_CUSTOMER_TOTAL_REFUND          = 'connector_data_mapping/customer_data/total_refund';
    const XML_PATH_CONNECTOR_CUSTOMER_SUBSCRIBER_STATUS     = 'connector_data_mapping/customer_data/subscriber_status';

	/**
	 * Dynamic Content
	 */
    const XML_PATH_CONNECTOR_DYNAMIC_CONTENT_PASSCODE       = 'connector_dynamic_content/external_dynamic_content_urls/passcode';
	const XML_PATH_CONNECTOR_DYNAMIC_CONTENT_NOSTO          = 'connector_dynamic_content/nosto_recommendation/api';
    const XML_PATH_CONNECTOR_DYNAMIC_CONTENT_FEEFO_LOGON    = 'connector_dynamic_content/feefo_feedback_engine/logon';
    const XML_PATH_CONNECTOR_DYNAMIC_CONTENT_FEEFO_REVIEWS  = 'connector_dynamic_content/feefo_feedback_engine/reviews_per_product';
    const XML_PATH_CONNECTOR_DYNAMIC_CONTENT_FEEFO_TEMPLATE  = 'connector_dynamic_content/feefo_feedback_engine/template';

	/**
     * CONFIGURATION SECTION.
     */
	const XML_PATH_CONNECTOR_SYNC_ORDER_STATUS              = 'connector_configuration/transactional_data/order_statuses';
	const XML_PATH_CONNECTOR_CUSTOM_ORDER_ATTRIBUTES        = 'connector_configuration/transactional_data/order_custom_attributes';
    const XML_PATH_CONNECTOR_CUSTOM_QUOTE_ATTRIBUTES        = 'connector_configuration/transactional_data/quote_custom_attributes';
	const XML_PATH_CONNECTOR_EMAIL_CAPTURE                  = 'connector_configuration/abandoned_carts/email_capture';
	const XML_PATH_CONNECTOR_ABANDONED_CART_LIMIT           = 'connector_configuration/abandoned_carts/limits';
	const XML_PATH_CONNECTOR_DISABLE_NEWSLETTER_SUCCESS     = 'connector_configuration/admin/disable_newsletter_success';
    const XML_PATH_CONNECTOR_DISABLE_CUSTOMER_SUCCESS       = 'connector_configuration/admin/disable_customer_success';
    const XML_PATH_CONNECTOR_DYNAMIC_STYLING                = 'connector_configuration/dynamic_content_style/dynamic_syling';
    const XML_PATH_CONNECTOR_DYNAMIC_NAME_COLOR             = 'connector_configuration/dynamic_content_style/name_color';
    const XML_PATH_CONNECTOR_DYNAMIC_NAME_FONT_SIZE         = 'connector_configuration/dynamic_content_style/name_font_size';
    const XML_PATH_CONNECTOR_DYNAMIC_NAME_STYLE             = 'connector_configuration/dynamic_content_style/name_style';
    const XML_PATH_CONNECTOR_DYNAMIC_PRICE_COLOR            = 'connector_configuration/dynamic_content_style/price_color';
    const XML_PATH_CONNECTOR_DYNAMIC_PRICE_FONT_SIZE        = 'connector_configuration/dynamic_content_style/price_font_size';
    const XML_PATH_CONNECTOR_DYNAMIC_PRICE_STYLE            = 'connector_configuration/dynamic_content_style/price_style';
    const XML_PATH_CONNECTOR_DYNAMIC_LINK_COLOR             = 'connector_configuration/dynamic_content_style/link_color';
    const XML_PATH_CONNECTOR_DYNAMIC_LINK_FONT_SIZE         = 'connector_configuration/dynamic_content_style/link_font_size';
    const XML_PATH_CONNECTOR_DYNAMIC_LINK_STYLE             = 'connector_configuration/dynamic_content_style/link_style';
    const XML_PATH_CONNECTOR_DYNAMIC_DOC_FONT               = 'connector_configuration/dynamic_content_style/font_picker';
    const XML_PATH_CONNECTOR_DYNAMIC_DOC_BG_COLOR           = 'connector_configuration/dynamic_content_style/doc_color';
    const XML_PATH_CONNECTOR_DYNAMIC_OTHER_COLOR             = 'connector_configuration/dynamic_content_style/other_color';
    const XML_PATH_CONNECTOR_DYNAMIC_OTHER_FONT_SIZE         = 'connector_configuration/dynamic_content_style/other_font_size';
    const XML_PATH_CONNECTOR_DYNAMIC_OTHER_STYLE             = 'connector_configuration/dynamic_content_style/other_style';
	const XML_PATH_CONNECTOR_RESOURCE_ALLOCATION            = 'connector_developer_settings/import_settings/memory_limit';
	const XML_PATH_CONNECTOR_SYNC_LIMIT                     = 'connector_developer_settings/import_settings/batch_size';
	const XML_PATH_CONNECTOR_TRANSACTIONAL_DATA_SYNC_LIMIT  = 'connector_developer_settings/import_settings/orders';
	const XML_PATH_CONNECTOR_ABANDONED_CART_SHELL           = 'connector_developer_settings/abandoned_cart/shell';
	const XML_PATH_CONNECTOR_SETUP_DATAFIELDS               = 'connector_developer_settings/sync_settings/setup_data_fields';
	const XML_PATH_CONNECTOR_ADVANCED_DEBUG_ENABLED         = 'connector_developer_settings/debug/debug_enabled';
	const XML_PATH_CONNECTOR_DEBUG_API_CALLS                = 'connector_developer_settings/debug/debug_api_calls';
	const XML_PATH_RAYGUN_APPLICATION_CODE                  = 'connector_developer_settings/debug/raygun_code';
	const XML_PATH_CONNECTOR_FEED_ENABLED                   = 'connector_developer_settings/feed_configuration/feed_enabled';
	const XML_PATH_CONNECTOR_FEED_URL                       = 'connector_developer_settings/feed_configuration/feed_url';
	const XML_PATH_CONNECTOR_FEED_FREQUENCY                 = 'connector_developer_settings/feed_configuration/frequency';
	const XML_PATH_CONNECTOR_FEED_USE_HTTPS                 = 'connector_developer_settings/feed_configuration/use_https';


    /**
     * Automation studio.
     */
	const XML_PATH_CONNECTOR_AUTOMATION_STUDIO_CUSTOMER      = 'connector_automation_studio/automation/customer_automation';
	const XML_PATH_CONNECTOR_AUTOMATION_STUDIO_SUBSCRIBER    = 'connector_automation_studio/automation/subscriber_automation';
    const XML_PATH_CONNECTOR_AUTOMATION_STUDIO_ORDER         = 'connector_automation_studio/automation/order_automation';
    const XML_PATH_CONNECTOR_AUTOMATION_STUDIO_GUEST_ORDER   = 'connector_automation_studio/automation/guest_order_automation';
    const XML_PATH_CONNECTOR_AUTOMATION_STUDIO_REVIEW        = 'connector_automation_studio/automation/review_automation';
    CONST XML_PATH_CONNECTOR_AUTOMATION_STUDIO_WISHLIST      = 'connector_automation_studio/automation/wishlist_automation';


    /**
     * ROI SECTION.
     */
    const XML_PATH_CONNECTOR_ROI_TRACKING_ENABLED           = 'connector_roi_tracking/roi_tracking/enabled';
    const XML_PATH_CONNECTOR_PAGE_TRACKING_ENABLED          = 'connector_roi_tracking/page_tracking/enabled';

    /**
     * OAUTH
     */
    const API_CONNECTOR_URL_AUTHORISE                       = 'https://my.dotmailer.com/OAuth2/authorise.aspx?';
    const API_CONNECTOR_URL_TOKEN                           = 'https://my.dotmailer.com/OAuth2/Tokens.ashx';
    const API_CONNECTOR_URL_LOG_USER                        = 'https://my.dotmailer.com/?oauthtoken=';

    /**
     * Email Config.
     */
    const CONNECTOR_EMAIL_CONFIG_LAST_RUN                   = 'connector_api_last_call';
    const CONNECTOR_EMAIL_CONFIG_HOUR_TRIGGER               = 'connector_api_hour_trigger';
    const CONNECTOR_FEED_LAST_CHECK_TIME                    = 'connector_feed_last_check_time';


    /**
     * Transactional Emails.
     */
    const XML_PATH_TRANSACTIONAL_API_ENABLED                = 'connector_transactional_emails/credentials/enabled';

    /**
     * Reviews
     */
    const XML_PATH_REVIEWS_ENABLED                          = 'connector_reviews/settings/enabled';

	/**
	 * Nosto
	 */
	const API_ENDPOINT                                      = 'https://api.nosto.com';
	const API_ENDPOINT_TEST                                 = 'https://test.api.nosto.com';


}