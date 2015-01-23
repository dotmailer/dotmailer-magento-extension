<?php

$installer = $this;


$installer->startSetup();


$installer->run("
    ALTER TABLE `{$this->getTable('email_contact')}`

      DROP FOREIGN KEY `FK_EMAIL_CONTACT_WEBSITE_ID_CORE_WEBSITE_WEBSITE_ID`,
      CHANGE COLUMN `website_id` `website_id` smallint(5) unsigned DEFAULT '0' AFTER `customer_id`;
");

$installer->run("
    UPDATE `{$this->getTable('email_contact')}` SET `website_id` = '0'
      WHERE `website_id` IS NULL;
");

$installer->run("
    ALTER TABLE `{$this->getTable('email_contact')}`
      ADD CONSTRAINT `FK_EMAIL_CONTACT_WEBSITE_ID_CORE_WEBSITE_WEBSITE_ID` FOREIGN KEY (`website_id`) REFERENCES `{$installer->getTable('core_website')}` (`website_id`) ON DELETE CASCADE ON UPDATE CASCADE;

");


$installer->endSetup();