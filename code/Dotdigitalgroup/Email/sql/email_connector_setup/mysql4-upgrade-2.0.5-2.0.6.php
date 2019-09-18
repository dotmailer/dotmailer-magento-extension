<?php


$installer = $this;

$installer->startSetup();
/**
 * Campaign table.
 */
$campaignTable = $installer->getTable('email_connector/campaign');

if ($installer->getConnection()->isTableExists($campaignTable)) {
    $installer->getConnection()->dropTable($campaignTable);
}

$table = $installer->getConnection()->newTable($campaignTable);
$table->addColumn(
    'id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
    'primary' => true,
    'identity' => true,
    'unsigned' => true,
    'nullable' => false
    ), 'Primary Key'
)
    ->addColumn(
        'campaign_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned' => true,
        'nullable' => false,
        ), 'Campaign ID'
    )
    ->addColumn(
        'email', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
        'nullable' => false,
        'default'  => ''
        ), 'Contact Email'
    )
    ->addColumn(
        'customer_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned' => true,
        'nullable' => false,
        ), 'Customer ID'
    )
    ->addColumn(
        'is_sent', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'unsigned' => true,
        'nullable' => true,
        ), 'Is Sent'
    )
    ->addColumn(
        'sent_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(),
        'Send Date'
    )
    ->addColumn(
        'order_increment_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned' => true,
        'nullable' => false,
        ), 'Order Increment ID'
    )
    ->addColumn(
        'quote_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned' => true,
        'nullable' => false,
        ), 'Sales Quote ID'
    )
    ->addColumn(
        'message', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
        'nullable' => false,
        'default'  => ''
        ), 'Error Message'
    )
    ->addColumn(
        'checkout_method', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
        'nullable' => false,
        'default'  => ''
        ), 'Checkout Method Used'
    )
    ->addColumn(
        'store_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned' => true,
        'nullable' => false,
        'default'  => '0'
        ), 'Store ID'
    )
    ->addColumn(
        'event_name', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
        'nullable' => false,
        'default'  => ''
        ), 'Event Name'
    )
    ->addColumn(
        'created_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(),
        'Creation Time'
    )
    ->addColumn(
        'updated_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(),
        'Update Time'
    )
    ->addIndex(
        $this->getIdxName($campaignTable, array('store_id')),
        array('store_id')
    )
    ->addIndex(
        $this->getIdxName($campaignTable, array('campaign_id')),
        array('campaign_id')
    )
    ->addIndex(
        $this->getIdxName($campaignTable, array('email')),
        array('email')
    )
    ->addIndex(
        $this->getIdxName($campaignTable, array('is_sent')),
        array('is_sent')
    )
    ->addForeignKey(
        $installer->getFkName(
            $campaignTable, 'store_id', 'core/store', 'store_id'
        ),
        'store_id', $installer->getTable('core/store'), 'store_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE
    )
    ->setComment('Connector Campaigns');
$installer->getConnection()->createTable($table);

/**
 * remove contact column
 */
$installer->getConnection()->dropColumn(
    $installer->getTable('email_connector/contact'), 'subscriber_imported'
);

/*
 * Cleaning
 */
$entityTypeId = $installer->getEntityTypeId('customer');
if ($installer->attributeExists($entityTypeId, 'dotmailer_contact_id')) {
    $installer->removeAttribute($entityTypeId, 'dotmailer_contact_id');
}

$entityTypeId = $installer->getEntityTypeId('order');
if ($installer->attributeExists($entityTypeId, 'dotmailer_order_imported')) {
    $installer->removeAttribute($entityTypeId, 'dotmailer_order_imported');
}


$installer->endSetup();