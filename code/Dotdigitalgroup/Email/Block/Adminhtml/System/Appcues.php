<?php

class Dotdigitalgroup_Email_Block_Adminhtml_System_Appcues
    extends Mage_Core_Block_Template
{
    public $sections
        = array(
            'connector_api_credentials',
            'connector_data_mapping',
            'connector_sync_settings',
            'connector_lost_baskets',
            'connector_automation_studio',
            'connector_dynamic_content',
            'connector_transactional_emails',
            'connector_configuration',
            'connector_developer_settings'
        );

    public $handles
        = array(
            'adminhtml_email_dashboard_index',
            'adminhtml_email_importer_index',
            'adminhtml_email_order_index',
            'adminhtml_email_quote_index',
            'adminhtml_email_review_index',
            'adminhtml_email_studio_index',
            'adminhtml_email_contact_index',
            'adminhtml_email_wishlist_index',
            'adminhtml_email_campaign_index',
            'adminhtml_email_automation_index',
            'adminhtml_email_catalog_index',
            'adminhtml_email_rules_index',
            'adminhtml_email_rules_edit',
            'adminhtml_customer_edit'
        );

    /**
     * Check if can run script if certain conditions matches
     *
     * @return bool
     */
    public function canRunScript()
    {
        //Check if current section is in predefined sections array
        $section = $this->getRequest()->getParam('section');
        if (in_array($section, $this->sections)) {
            return true;
        }

        //Check if current handle is in predefined handles array
        $handles = $this->getLayout()->getUpdate()->getHandles();
        foreach ($handles as $handle) {
            if (in_array($handle, $this->handles)) {
                return true;
            }
        }

        return false;
    }
}