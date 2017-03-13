<?php
//@codingStandardsIgnoreStart
$installer = $this;

$installer->startSetup();


$installer->run(
    "DROP TABLE IF EXISTS {$this->getTable('email_contact')};
        CREATE TABLE `{$this->getTable('email_contact')}` (
          `email_contact_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `is_guest` smallint(1) DEFAULT NULL,
          `contact_id` int(15) unsigned DEFAULT NULL,
          `customer_id` int(10) unsigned DEFAULT NULL,
          `website_id` smallint(5) unsigned DEFAULT NULL COMMENT 'Website Id',
          `email` varchar(255) DEFAULT NULL,
          `is_subscriber` tinyint(1) unsigned DEFAULT NULL,
          `subscriber_status` int(10) unsigned DEFAULT '0',
          `subscriber_imported` tinyint(1) unsigned DEFAULT NULL,
          `email_imported` tinyint(1) unsigned DEFAULT NULL,
          `suppressed` smallint(1) DEFAULT NULL,
          PRIMARY KEY (`email_contact_id`),
          KEY `IDX_EMAIL_CONTACT_ID` (`email_contact_id`),
          KEY `IDX_EMAIL_CONTACT_IS_GUEST` (`is_guest`),
          KEY `IDX_EMAIL_CONTACT_CUSTOMER_ID` (`customer_id`),
          KEY `IDX_EMAIL_CONTACT_WEBSITE_ID` (`website_id`),
          KEY `IDX_EMAIL_CONTACT_IS_SUBSCRIBER` (`is_subscriber`),
          KEY `IDX_EMAIL_CONTACT_SUBSCRIBER_STATUS` (`subscriber_status`),
          KEY `IDX_EMAIL_CONTACT_SUBSCRIBER_IMPORTED` (`subscriber_imported`),
          KEY `IDX_EMAIL_CONTACT_EMAIL_IMPORTED` (`email_imported`),
          KEY `IDX_EMAIL_CONTACT_suppressed` (`suppressed`),
	      UNIQUE KEY `IDX_EMAIL_CONTACT_EMAIL` (`email`),
          CONSTRAINT `FK_EMAIL_CONTACT_WEBSITE_ID_CORE_WEBSITE_WEBSITE_ID` FOREIGN KEY (`website_id`) 
          REFERENCES `core_website` (`website_id`) ON DELETE SET NULL ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Email Contacts';"
);


//Insert and populate email contact table
$installer->run(
    "INSERT IGNORE INTO `{$this->getTable('email_contact')}` (`customer_id`, `email`, `website_id`)
                SELECT `entity_id`, `email`, `website_id`
                FROM `{$this->getTable('customer_entity')}`;"
);

//Subscribers that are not customers
$installer->run(
    "
          INSERT IGNORE INTO {$this->getTable('email_contact')} (`email`, `is_subscriber`, `subscriber_status`)
          SELECT `subscriber_email`, '1' as col2, '1' as col3 FROM `{$this->getTable('newsletter/subscriber')}` 
          WHERE `customer_id` = 0 AND `subscriber_status` = 1;
        "
);


$installer->endSetup();