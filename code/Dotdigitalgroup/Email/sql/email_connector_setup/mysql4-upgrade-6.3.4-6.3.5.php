<?php
//@codingStandardsIgnoreStart
/** @var Mage_Eav_Model_Entity_Setup $installer */

$installer = $this;
$installer->startSetup();

$abandonedTable = $installer->getTable('ddg_automation/abandoned');

if ($installer->getConnection()->isTableExists($abandonedTable)) {
    $installer->getConnection()->dropTable($abandonedTable);
}

$table = $installer->getConnection()->newTable($abandonedTable);
$table->addColumn(
    'id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
    'primary' => true,
    'identity' => true,
    'unsigned' => true,
    'nullable' => false
), 'Primary Key'
)
    ->addColumn(
        'quote_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned' => true,
        'nullable' => true,
    ), 'Quote Id'
    )
    ->addColumn(
        'store_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned' => true,
        'nullable' => true,
    ), 'Store Id'
    )
    ->addColumn(
        'customer_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned' => true,
        'nullable' => true,
    ), 'Customer ID'
    )
    ->addColumn(
        'email', Varien_Db_Ddl_Table::TYPE_VARCHAR, 150, array(
        'unsigned' => true,
        'nullable' => true,
    ), 'Customer email'
    )
    ->addColumn(
        'is_active', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'unsigned' => true,
        'nullable' => false,
        'default' => '1'
    ), 'Is Active'
    )
    ->addColumn(
        'quote_updated_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(),
        'Quote updated at'
    )
    ->addColumn(
        'abandoned_cart_number', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'unsigned' => true,
        'nullable' => false,
        'default' => '0'
    ), 'Abandoned Cart Number'
    )
    ->addColumn(
        'items_count', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'unsigned' => true,
        'nullable' => false,
        'default' => '0'
    ), 'Items count'
    )
    ->addColumn(
        'items_ids', Varien_Db_Ddl_Table::TYPE_VARCHAR, 50, array(
        'unsigned' => true,
        'nullable' => false,
    ), 'Items Id'
    )
    ->addColumn(
        'created_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(),
        'Created At'
    )
    ->addColumn(
        'updated_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(),
        'Update At'
    )
    ->addIndex(
        $installer->getIdxName($abandonedTable, array('quote_id')),
        array('quote_id'),
        array('type' => 'unique')
    )
    ->addIndex(
        $installer->getIdxName($abandonedTable, array('store_id')),
        array('store_id')
    )
    ->addIndex(
        $installer->getIdxName($abandonedTable, array('customer_id')),
        array('customer_id')
    )
    ->addIndex(
        $installer->getIdxName($abandonedTable, array('email')),
        array('email')
    )
    ->addForeignKey(
        $installer->getFkName(
            $abandonedTable, 'quote_id', 'sales/quote', 'entity_id'
        ),
        'quote_id', $installer->getTable('sales/quote'), 'entity_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE
    )
    ->setComment('Abandoned Carts');
$installer->getConnection()->createTable($table);

$installer->endSetup();