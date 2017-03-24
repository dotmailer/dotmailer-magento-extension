<?php
//@codingStandardsIgnoreStart
/** @var Mage_Eav_Model_Entity_Setup $installer */

$installer = $this;
$installer->startSetup();

/**
 * create quote table
 */
$quoteTable = $installer->getTable('ddg_automation/quote');

//drop table if exist
if ($installer->getConnection()->isTableExists($quoteTable)) {
    $installer->getConnection()->dropTable($quoteTable);
}

$table = $installer->getConnection()->newTable($quoteTable);
$table->addColumn(
    'id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
    'primary'  => true,
    'identity' => true,
    'unsigned' => true,
    'nullable' => false
    ), 'Primary Key'
)
    ->addColumn(
        'quote_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned' => true,
        'nullable' => false,
        ), 'Quote Id'
    )
    ->addColumn(
        'customer_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned' => true,
        'nullable' => false,
        ), 'Customer ID'
    )
    ->addColumn(
        'store_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned' => true,
        'nullable' => false,
        ), 'Store Id'
    )
    ->addColumn(
        'imported', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'unsigned' => true,
        'nullable' => true,
        ), 'Quote Imported'
    )
    ->addColumn(
        'modified', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'unsigned' => true,
        'nullable' => true,
        ), 'Quote Modified'
    )
    ->addColumn(
        'converted_to_order', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'unsigned' => true,
        'nullable' => true,
        ), 'Quote Converted To Order'
    )
    ->addColumn(
        'created_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(),
        'Creation Time'
    )
    ->addColumn(
        'updated_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(),
        'Update Time'
    )
    ->setComment('Connector Quotes');
$installer->getConnection()->createTable($table);

//populate quote table
$select = $installer->getConnection()->select()
    ->from(
        $installer->getTable('sales/quote'),
        array('quote_id' => 'entity_id', 'store_id', 'customer_id',
              'created_at')
    )
    ->where('customer_id !=?', null)
    ->where('is_active =?', 1)
    ->where('items_count >?', 0);

$insertArray = array('quote_id', 'store_id', 'customer_id', 'created_at');

$sqlQuery = $select->insertFromSelect($quoteTable, $insertArray, false);
$installer->getConnection()->query($sqlQuery);

$installer->endSetup();