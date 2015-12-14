<?php

/** @var Mage_Eav_Model_Entity_Setup $installer */

$installer = $this;
$installer->startSetup();

//drop table
$createTable = $installer->getTable('email_create');
if ($installer->getConnection()->isTableExists($createTable)) {
    $installer->getConnection()->dropTable($createTable);
}

//add columns to table
$campaignTable = $installer->getTable('ddg_automation/campaign');
$installer->getConnection()->addColumn($campaignTable, 'subject', array(
    'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
    'unsigned'  => true,
    'nullable' => false,
    'default' => '',
    'comment' => 'Email Subject'
));
$installer->getConnection()->addColumn($campaignTable, 'html_content', array(
    'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
    'unsigned'  => true,
    'nullable' => false,
    'default' => '',
    'comment' => 'Email Html Content'
));
$installer->getConnection()->addColumn($campaignTable, 'plain_text_content', array(
    'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
    'unsigned'  => true,
    'nullable' => false,
    'default' => '',
    'comment' => 'Email Plain Text Content'
));
$installer->getConnection()->addColumn($campaignTable, 'from_name', array(
    'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
    'unsigned'  => true,
    'nullable' => false,
    'default' => '',
    'comment' => 'Email From Name'
));
$installer->getConnection()->addColumn($campaignTable, 'create_message', array(
    'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
    'unsigned'  => true,
    'nullable' => false,
    'default' => '',
    'comment' => 'Create Campaign Message'
));
$installer->getConnection()->addColumn($campaignTable, 'contact_message', array(
    'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
    'unsigned'  => true,
    'nullable' => false,
    'default' => '',
    'comment' => 'Contact Message'
));
$installer->getConnection()->addColumn($campaignTable, 'is_created', array(
    'type' => Varien_Db_Ddl_Table::TYPE_SMALLINT,
    'unsigned'  => true,
    'nullable' => true,
    'comment' => 'Is Campaign Created'
));
$installer->getConnection()->addColumn($campaignTable, 'is_copy', array(
    'type' => Varien_Db_Ddl_Table::TYPE_SMALLINT,
    'unsigned'  => true,
    'nullable' => true,
    'comment' => 'Is Copy'
));
$installer->getConnection()->addColumn($campaignTable, 'type', array(
    'type' => Varien_Db_Ddl_Table::TYPE_SMALLINT,
    'unsigned' => true,
    'nullable' => false,
    'default' => '1',
    'comment' => 'Type. 1: Campaign, 2: Create'
));
$installer->getConnection()->addColumn($campaignTable, 'website_id', array(
    'type' => Varien_Db_Ddl_Table::TYPE_INTEGER,
    'unsigned' => true,
    'nullable' => false,
    'default' => '0',
    'comment' => 'Website Id'
));


/**
 * create Config table.
 */
$configTable = $installer->getTable('ddg_automation/config');

if ($installer->getConnection()->isTableExists($configTable)) {
	$installer->getConnection()->dropTable($configTable);
}

$table = $installer->getConnection()->newTable($configTable);
$table->addColumn('email_config_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
	'primary'  => true,
	'identity' => true,
	'unsigned' => true,
	'nullable' => false
), 'Primary Key')
      ->addColumn('path', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
	      'nullable' => true,
      ), 'Config Path')
      ->addColumn('scope', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
	      'nullable'  => true,
      ), 'Config Scope')
      ->addColumn('value', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
	      'nullable' => false,
	      'default'  => ''
      ), 'Config Value')
      ->addColumn('is_api', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
	      'unsigned'  => true,
	      'nullable'  => true,
      ), 'Only For Api Calls')
      ->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
      ), 'Creation Time')
      ->addColumn('updated_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
      ), 'Update Time')
      ->setComment('Connector Config Data');
$installer->getConnection()->createTable($table);

$installer->endSetup();
