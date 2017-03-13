<?php
//@codingStandardsIgnoreStart
/** @var Mage_Eav_Model_Entity_Setup $installer */

$installer = $this;

$installer->startSetup();

//Insert status column to email_order table
$orderTable = $installer->getTable('ddg_automation/order');

$installer->getConnection()->addColumn(
    $orderTable, 'order_status', array(
        'type'     => Varien_Db_Ddl_Table::TYPE_TEXT,
        'length'   => 256,
        'nullable' => false,
        'default'  => null,
        'comment'  => 'Order Status'
    )
);

//populate order status in email_order table

//select
$select = $installer->getConnection()->select();

//join
$select->joinLeft(
    array('sfo' => $installer->getTable('sales/order')),
    "eo.order_id = sfo.entity_id",
    array('order_status' => 'sfo.status')
);

//update query from select
$updateSql = $select->crossUpdateFromSelect(array('eo' => $orderTable));

//run query
$installer->getConnection()->query($updateSql);


/**
 * create table
 */
$createTable = $installer->getTable('ddg_automation/create');

if ($installer->getConnection()->isTableExists($createTable)) {
    $installer->getConnection()->dropTable($createTable);
}

$table = $installer->getConnection()->newTable($createTable);
$table->addColumn(
    'id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
    'primary'  => true,
    'identity' => true,
    'unsigned' => true,
    'nullable' => false
    ), 'Primary Key'
)
    ->addColumn(
        'email', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
        'nullable' => false,
        'default'  => ''
        ), 'Email'
    )
    ->addColumn(
        'from_name', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
        'nullable' => false,
        'default'  => ''
        ), 'From Name'
    )
    ->addColumn(
        'website_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned' => true,
        'nullable' => false,
        'default'  => '0'
        ), 'Website ID'
    )
    ->addColumn(
        'name', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        'unsigned' => true,
        'nullable' => false,
        ), 'Template Name'
    )
    ->addColumn(
        'subject', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        'unsigned' => true,
        'nullable' => false,
        ), 'Subject'
    )
    ->addColumn(
        'html_content', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        'unsigned' => true,
        'nullable' => false,
        ), 'Html Content'
    )
    ->addColumn(
        'plain_text_content', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        'unsigned' => true,
        'nullable' => false,
        ), 'Plain Text Content'
    )
    ->addColumn(
        'created_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(),
        'Creation Time'
    )
    ->addColumn(
        'message', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
        'nullable' => false,
        'default'  => ''
        ), 'Error Message'
    )
    ->addColumn(
        'is_created', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'unsigned' => true,
        'nullable' => true,
        ), 'Is Created'
    )
    ->addColumn(
        'copy', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
        'nullable' => false,
        'default'  => ''
        ), 'Copy Email'
    )
    ->addIndex(
        $this->getIdxName($createTable, array('is_created')),
        array('is_created')
    )
    ->setComment('Transactional Orders Data');
$installer->getConnection()->createTable($table);

//Save all order statuses as string to extension's config value
$source   = Mage::getModel('adminhtml/system_config_source_order_status');
$statuses = $source->toOptionArray();

if (! empty($statuses) && $statuses[0]['value'] == '') {
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

$installer->endSetup();

