<?php

/** @var Mage_Eav_Model_Entity_Setup $installer */

$installer = $this;
$installer->startSetup();

$automationTable = $this->getTable('ddg_automation/automation');

//drop table if exist
if ($installer->getConnection()->isTableExists($automationTable)) {
	$installer->getConnection()->dropTable($automationTable);
}
$table = $installer->getConnection()->newTable($automationTable);
$table->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
	'primary'  => true,
	'identity' => true,
	'unsigned' => true,
	'nullable' => false
	), 'Primary Key')
	->addColumn('automation_type', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
		'nullable' => true,
	), 'Automation Type')
	->addColumn('store_name', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
		'nullable' => true,
	), 'Automation Type')
	->addColumn('enrolment_status', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
	      'nullable'  => false,
	), 'Entrolment Status')
	->addColumn('email', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
	      'nullable'  => true,
	), 'Email')
	->addColumn('type_id', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
	      'nullable'  => true,
	), 'Type ID')
	->addColumn('program_id', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
		'nullable'  => true,
	), 'Program ID')
	->addColumn('website_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
		'unsigned'  => true,
		'nullable' => false,
	), 'Website Id')
	->addColumn('message', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
		'nullable'  => false,
	), 'Message')
	->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
	), 'Creation Time')
	->addColumn('updated_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
	), 'Update Time')
	->addIndex($this->getIdxName($automationTable, array('automation_type')),
	array('automation_type'))
	->addIndex($this->getIdxName($automationTable, array('enrolment_status')),
	array('enrolment_status'))
	->setComment('Automation Status');
$installer->getConnection()->createTable($table);

$installer->endSetup();