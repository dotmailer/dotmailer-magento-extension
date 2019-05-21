<?php

class Dotdigitalgroup_Email_Helper_Setup extends Mage_Core_Helper_Abstract
{
    /**
     * @return bool
     */
    public function skipMigrateData()
    {
        $website = Mage::app()->getWebsite(0);
        return (bool)$website->getConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_INSTALL_SKIP_DATA_MIGRATION
        );
    }

    /**
     * @param int $value
     */
    public function setSkipMigrateDataFlag($value)
    {
        $config = new Mage_Core_Model_Config();
        $config->saveConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_INSTALL_SKIP_DATA_MIGRATION,
            $value
        );
    }

    /**
     * @return int
     */
    public function getBatchSize()
    {
        $website = Mage::app()->getWebsite(0);
        return (int)$website->getConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_DATA_MIGRATION_BATCH_SIZE
        );
    }

    /**
     * @param int $batchSize
     */
    public function setBatchSize($batchSize)
    {
        $config = new Mage_Core_Model_Config();
        $config->saveConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_DATA_MIGRATION_BATCH_SIZE,
            $batchSize
        );
    }

    /**
     * @param $size
     * @return $this
     */
    public function populateAllEmailTables($size = 0)
    {
        $batchSize =  $size ? $size : $this->getBatchSize();

        /** @var Dotdigitalgroup_Email_Model_Resource_Contact $contactResource */
        $contactResource = Mage::getResourceModel('ddg_automation/contact');

        $this->truncateAllTables($contactResource);

        $contactResource->populateEmailContactTable($batchSize)
            ->populateSubscribersThatAreNotCustomers($batchSize)
            ->updateCustomersThatAreSubscribers()
            ->updateContactsWithSegmentsIdsForEnterprise();

        /** @var Dotdigitalgroup_Email_Model_Resource_Order $orderResource */
        $orderResource = Mage::getResourceModel('ddg_automation/order');
        $orderResource->populateEmailOrderTable($batchSize);

        /** @var Dotdigitalgroup_Email_Model_Resource_Review $reviewResource */
        $reviewResource = Mage::getResourceModel('ddg_automation/review');
        $reviewResource->populateEmailReviewTable($batchSize);

        /** @var Dotdigitalgroup_Email_Model_Resource_Wishlist $wishlistResource */
        $wishlistResource = Mage::getResourceModel('ddg_automation/wishlist');
        $wishlistResource->populateEmailWishlistTable($batchSize);

        /** @var Dotdigitalgroup_Email_Model_Resource_Quote $quoteResource */
        $quoteResource = Mage::getResourceModel('ddg_automation/quote');
        $quoteResource->populateEmailQuoteTable($batchSize);

        /** @var Dotdigitalgroup_Email_Model_Resource_Catalog $catalogResource */
        $catalogResource = Mage::getResourceModel('ddg_automation/catalog');
        $catalogResource->populateEmailCatalogTable($batchSize);

        return $this;
    }

    /**
     * @param Dotdigitalgroup_Email_Model_Resource_Contact $contactResource
     */
    protected function truncateAllTables($contactResource)
    {
        $tables = array(
            'email_contact_id' => $contactResource->getTable('ddg_automation/contact'),
            'email_order_id' => $contactResource->getTable('ddg_automation/order'),
            $contactResource->getTable('ddg_automation/review'),
            $contactResource->getTable('ddg_automation/wishlist'),
            $contactResource->getTable('ddg_automation/quote'),
            $contactResource->getTable('ddg_automation/catalog')
        );
        foreach ($tables as $index => $table) {
            $connection = $contactResource->getReadConnection();
            if ($connection->isTableExists($table)) {
                $tableIdFieldName = strlen($index) > 1 ? $index : 'id';
                $maxIdResult = $connection->fetchRow(
                   sprintf(
                       'SELECT %s as max_id from %s ORDER BY %s DESC LIMIT 1',
                       $tableIdFieldName,
                       $table,
                       $tableIdFieldName
                   )
                );
                $maxId = $maxIdResult['max_id'];
                $query = "delete from %s where %s >= 1 and %s <= %d";
                $connection->query(sprintf($query, $table, $tableIdFieldName, $tableIdFieldName, $maxId));
                $connection->changeTableAutoIncrement($table, 1);
            }
        }
    }

    /**
     * Save configurations in config table
     * @return $this
     */
    public function saveConfigurationsInConfigTable()
    {
        $this->saveProductTypesAndVisibilitiesInConfig();
        $this->saveOrderStatusesAsStringInConfig();
        $this->setAdminNotificationMessage();
        $this->generateAndSaveCode();

        //clean the cache for config
        Mage::getModel('core/config')->cleanCache();
        return $this;
    }

    /**
     * Encrypt api password and user token
     * @return $this
     */
    public function encryptApiPasswordAndUserToken()
    {
        $configData = Mage::getModel('core/config_data')->getCollection()
            ->addFieldToFilter('path', Dotdigitalgroup_Email_Helper_Transactional::XML_PATH_DDG_TRANSACTIONAL_PASSWORD);
        foreach ($configData as $config) {
            $value = $config->getValue();
            //pass value not empty
            if ($value) {
                $config->setValue(Mage::helper('core')->encrypt($value))
                    ->save();
            }
        }

        //admin users token
        $adminUsers = Mage::getModel('admin/user')->getCollection()
            ->addFieldToFilter('refresh_token', array('notnull' => true));
        /** @var Mage_Admin_Model_User $adminUser */
        foreach ($adminUsers as $adminUser) {
            $token = $adminUser->getRefreshToken();
            $adminUser->setRefreshToken(Mage::helper('core')->encrypt($token))
                ->save();
        }

        //clean the cache for config
        Mage::getModel('core/config')->cleanCache();
        return $this;
    }

    /**
     * Save config for allow non subscriber for features; AC, Order review and Trigger campaign
     * @return $this
     */
    public function saveAllowNonSubscriberConfig()
    {
        $configModel = Mage::getModel('core/config');

        //For AC
        $configModel->saveConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CONTENT_ALLOW_NON_SUBSCRIBERS,
            1
        );

        //For order review
        $configModel->saveConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_REVIEW_ALLOW_NON_SUBSCRIBERS,
            1
        );

        //For Sync
        $configModel->saveConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_ALLOW_NON_SUBSCRIBERS,
            1
        );

        //clean the cache for config
        Mage::getModel('core/config')->cleanCache();
        return $this;
    }

    /**
     * Save product types and visibilities in config
     */
    private function saveProductTypesAndVisibilitiesInConfig()
    {
        //Type
        $configModel = Mage::getModel('core/config');
        $types = Mage::getModel('ddg_automation/adminhtml_source_sync_catalog_type')
            ->toOptionArray();
        $options = array();
        foreach ($types as $type) {
            $options[] = $type['value'];
        }
        $typeString = implode(',', $options);
        $configModel->saveConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_CATALOG_TYPE,
            $typeString
        );

        //Visibility
        $visibilities = Mage::getModel(
            'ddg_automation/adminhtml_source_sync_catalog_visibility'
        )->toOptionArray();
        $options = array();
        foreach ($visibilities as $visibility) {
            $options[] = $visibility['value'];
        }
        $visibilityString = implode(',', $options);
        $configModel->saveConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_CATALOG_VISIBILITY,
            $visibilityString
        );
    }

    /**
     * Save order statuses in config
     */
    private function saveOrderStatusesAsStringInConfig()
    {
        $source = Mage::getModel('adminhtml/system_config_source_order_status');
        $statuses = $source->toOptionArray();

        if (count($statuses) > 0 && $statuses[0]['value'] == '') {
            array_shift($statuses);
        }

        $options = array();
        foreach ($statuses as $status) {
            $options[] = $status['value'];
        }

        $statusString = implode(',', $options);

        $configModel = Mage::getModel('core/config');
        $configModel->saveConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_ORDER_STATUS,
            $statusString
        );
    }

    /**
     * Set admin notification
     */
    private function setAdminNotificationMessage()
    {
        $adminData = array();
        $adminData[] = array(
            'severity' => 4,
            'date_added' => gmdate('Y-m-d H:i:s', time()),
            'title' => 'Email Connector Was Installed. Please Enter Your API Credentials & Ensure Cron Jobs Are 
                Running On Your Site (Find Out More)',
            'description' => 'Email Connector Was Installed. Please Enter Your API Credentials & Ensure Cron Jobs Are 
                Running On Your Site.',
            'url' => 'http://www.magentocommerce.com/wiki/1_-_installation_and_configuration/how_to_setup_a_cron_job'
        );
        Mage::getModel('adminnotification/inbox')->parse($adminData);
    }

    /**
     *  Generate random string and save in config
     */
    private function generateAndSaveCode()
    {
        $code = Mage::helper('core')->getRandomString(32);
        $configModel = Mage::getModel('core/config');
        $configModel->saveConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_DYNAMIC_CONTENT_PASSCODE,
            $code
        );
    }
}