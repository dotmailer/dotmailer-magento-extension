<?php

//@codingStandardsIgnoreStart
$installer = $this;

$installer->startSetup();

try {
    $installer->run(
        "DROP TABLE IF EXISTS {$this->getTable('email_order')};
        CREATE TABLE `{$this->getTable('email_order')}` (
          `email_order_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
          `order_id` int(15) unsigned  DEFAULT NULL,
          `quote_id` int(15) unsigned  DEFAULT NULL,
          `store_id` smallint(5) unsigned DEFAULT NULL,
          `email_imported` tinyint(1) DEFAULT NULL,
          `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
          `updated_at` datetime DEFAULT NULL,
          PRIMARY KEY (`email_order_id`),
          KEY `IDX_EMAIL_STORE_ID` (`store_id`),
          KEY `IDX_EMAIL_QUOTE_ID` (`quote_id`),
          CONSTRAINT `FK_EMAIL_STORE_ID_CORE_STORE_STORE_ID` FOREIGN KEY (`store_id`) REFERENCES 
          `{$this->getTable('core_store')}` (`store_id`) ON DELETE SET NULL ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
    );


    //Insert and populate email order the table
    $installer->run(
        "INSERT IGNORE INTO  `{$this->getTable('email_order')}` (`order_id`, `quote_id`, `store_id`, `created_at`,
        `updated_at`)
        SELECT `entity_id`, `quote_id`, `store_id`, `created_at`, `updated_at`
        FROM `{$this->getTable('sales/order')}`;"
    );


    $installer->run(
        "DROP TABLE IF EXISTS {$this->getTable('email_contact')};
        CREATE TABLE `{$this->getTable('email_contact')}` (
          `email_contact_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `is_guest` smallint(1) DEFAULT NULL,
          `contact_id` int(15) unsigned DEFAULT NULL,
          `customer_id` int(10) unsigned DEFAULT NULL,
          `website_id` smallint(5) unsigned DEFAULT NULL COMMENT 'Website Id',
          `email` varchar(255) DEFAULT NULL,
          `is_subscriber` tinyint(1)unsigned DEFAULT NULL,
          `subscriber_status` int(10) unsigned DEFAULT '0',
          `email_id` smallint(5) unsigned DEFAULT NULL,
          `email_imported` tinyint(1) unsigned DEFAULT NULL,
          `suppressed` smallint(1) DEFAULT NULL,
          PRIMARY KEY (`email_contact_id`),
          KEY `IDX_EMAIL_CONTACT_WEBSITE_ID` (`website_id`),
          CONSTRAINT `FK_EMAIL_CONTACT_WEBSITE_ID_CORE_WEBSITE_WEBSITE_ID` FOREIGN KEY (`website_id`) REFERENCES 
          `core_website` (`website_id`) ON DELETE SET NULL ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Email Contact Sync';"
    );


    //Insert and populate email contact table
    $installer->run(
        "INSERT IGNORE INTO `{$this->getTable('email_contact')}` (`customer_id`, `email`, `website_id`)
        SELECT `entity_id`, `email`, `website_id`
        FROM `{$this->getTable('customer_entity')}`;"
    );

    //Remove Order Attribute For Imported Data
    $installer->removeAttribute('order', 'dotmailer_order_imported');
    $installer->removeAttribute('customer', 'dotmailer_contact_id');
} catch (Exception $e) {
    Mage::log($e->getMessage());
}

$installer->endSetup();