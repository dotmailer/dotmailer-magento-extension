<?php

/** @var Mage_Eav_Model_Entity_Setup $installer */

$installer = $this;
$installer->startSetup();

/**
 * create catalog table.
 */
$catalogTable = $installer->getTable('ddg_automation/catalog');

if ($installer->getConnection()->isTableExists($catalogTable)) {
    $installer->getConnection()->dropTable($catalogTable);
}
$table = $installer->getConnection()->newTable($catalogTable);
$table->addColumn(
    'id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
    'primary'  => true,
    'identity' => true,
    'unsigned' => true,
    'nullable' => false
    ), 'Primary Key'
)
    ->addColumn(
        'product_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned' => true,
        'nullable' => false,
        ), 'Product Id'
    )
    ->addColumn(
        'imported', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'unsigned' => true,
        'nullable' => true,
        ), 'Product Imported'
    )
    ->addColumn(
        'modified', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'unsigned' => true,
        'nullable' => true,
        ), 'Product Modified'
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
        $this->getIdxName($catalogTable, array('product_id')),
        array('product_id')
    )
    ->addIndex(
        $this->getIdxName($catalogTable, array('imported')),
        array('imported')
    )
    ->addIndex(
        $this->getIdxName($catalogTable, array('modified')),
        array('modified')
    )
    ->setComment('Connector Catalog');
$installer->getConnection()->createTable($table);

/**
 * Populate catalog table
 */
$select      = $installer->getConnection()->select()
    ->from(
        array('catalog' => $this->getTable('catalog_product_entity')),
        array('product_id' => 'catalog.entity_id',
              'created_at' => 'catalog.created_at')
    );
$insertArray = array('product_id', 'created_at');
$sqlQuery    = $select->insertFromSelect($catalogTable, $insertArray, false);
$installer->getConnection()->query($sqlQuery);

/**
 * create rules table.
 */
$rulesTable = $installer->getTable('ddg_automation/rules');

if ($installer->getConnection()->isTableExists($rulesTable)) {
    $installer->getConnection()->dropTable($rulesTable);
}
$table = $installer->getConnection()->newTable($rulesTable);
$table->addColumn(
    'id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
    'primary'  => true,
    'identity' => true,
    'unsigned' => true,
    'nullable' => false
    ), 'Primary Key'
)
    ->addColumn(
        'name', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
        'nullable' => false,
        'default'  => ''
        ), 'Rule Name'
    )
    ->addColumn(
        'website_ids', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
        'nullable' => false,
        'default'  => '0'
        ), 'Website Id'
    )
    ->addColumn(
        'type', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'nullable' => false,
        'default'  => 0
        ), 'Rule Type'
    )
    ->addColumn(
        'status', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'nullable' => false,
        'default'  => 0
        ), 'Status'
    )
    ->addColumn(
        'combination', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'nullable' => false,
        'default'  => '1'
        ), 'Rule Condition'
    )
    ->addColumn(
        'condition', Varien_Db_Ddl_Table::TYPE_BLOB, null, array(
        'nullable' => false,
        'default'  => ''
        ), 'Rule Condition'
    )
    ->addColumn(
        'created_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(),
        'Creation Time'
    )
    ->addColumn(
        'updated_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(),
        'Update Time'
    )
    ->setComment('Connector Rules');
$installer->getConnection()->createTable($table);

$configModel = Mage::getModel('core/config');
//Save all product types as string to extension's config value
$types   = Mage::getModel('ddg_automation/adminhtml_source_sync_catalog_type')
    ->toOptionArray();
$options = array();
foreach ($types as $type) {
    $options[] = $type['value'];
}
$typeString = implode(',', $options);

//Save all product visibilities as string to extension's config value
$visibilities = Mage::getModel(
    'ddg_automation/adminhtml_source_sync_catalog_visibility'
)->toOptionArray();
$options      = array();
foreach ($visibilities as $visibility) {
    $options[] = $visibility['value'];
}
$visibilityString = implode(',', $options);

//save config value
$configModel->saveConfig(
    Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_CATALOG_TYPE,
    $typeString
);
$configModel->saveConfig(
    Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_CATALOG_VISIBILITY,
    $visibilityString
);


/**
 * create email importer table.
 */
$importerTable = $installer->getTable('ddg_automation/importer');

if ($installer->getConnection()->isTableExists($importerTable)) {
    $installer->getConnection()->dropTable($importerTable);
}
$table = $installer->getConnection()->newTable($importerTable);
$table->addColumn(
    'id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
    'primary'  => true,
    'identity' => true,
    'unsigned' => true,
    'nullable' => false
    ), 'Primary Key'
)
    ->addColumn(
        'import_type', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
        'nullable' => false,
        'default'  => ''
        ), 'Import Type'
    )
    ->addColumn(
        'website_id', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
        'nullable' => false,
        'default'  => '0'
        ), 'Website Id'
    )
    ->addColumn(
        'import_status', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'nullable' => false,
        'default'  => 0
        ), 'Import Status'
    )
    ->addColumn(
        'import_id', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
        'nullable' => false,
        'default'  => ''
        ), 'Import Id'
    )
    ->addColumn(
        'import_data', Varien_Db_Ddl_Table::TYPE_BLOB, '2M', array(
        'nullable' => false,
        'default'  => ''
        ), 'Import Data'
    )
    ->addColumn(
        'import_mode', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
        'nullable' => false,
        'default'  => ''
        ), 'Import Mode'
    )
    ->addColumn(
        'import_file', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        'nullable' => false,
        'default'  => ''
        ), 'Import File'
    )
    ->addColumn(
        'message', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
        'nullable' => false,
        'default'  => ''
        ), 'Error Message'
    )
    ->addColumn(
        'created_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(),
        'Creation Time'
    )
    ->addColumn(
        'updated_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(),
        'Update Time'
    )
    ->addColumn(
        'import_started', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(),
        'Import Started'
    )
    ->addColumn(
        'import_finished', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(),
        'Import Finished'
    )
    ->addIndex(
        $this->getIdxName($importerTable, array('import_type')),
        array('import_type')
    )
    ->addIndex(
        $this->getIdxName($importerTable, array('website_id')),
        array('website_id')
    )
    ->addIndex(
        $this->getIdxName($importerTable, array('import_status')),
        array('import_status')
    )
    ->addIndex(
        $this->getIdxName($importerTable, array('import_mode')),
        array('import_mode')
    )
    ->setComment('Email Importer');
$installer->getConnection()->createTable($table);

//modify email_order table
$orderTable = $installer->getTable('ddg_automation/order');
$installer->getConnection()->addColumn(
    $orderTable, 'modified', array(
        'type'     => Varien_Db_Ddl_Table::TYPE_SMALLINT,
        'unsigned' => true,
        'nullable' => true,
        'comment'  => 'Order Modified'
    )
);

/**
 * drop config table
 */
$configTable = $this->getTable('ddg_automation/config');

//drop config table if exist
if ($installer->getConnection()->isTableExists($configTable)) {
    $installer->getConnection()->dropTable($configTable);
}

/**
 * modify email_quote table
 */
$quoteTable = $installer->getTable('ddg_automation/quote');

//remove column
$installer->getConnection()->dropColumn($quoteTable, 'converted_to_order');

//add indexes to quote table
$installer->getConnection()->addIndex(
    $quoteTable,
    $this->getIdxName($quoteTable, array('store_id')),
    array('store_id')
);
$installer->getConnection()->addIndex(
    $quoteTable,
    $this->getIdxName($quoteTable, array('customer_id')),
    array('customer_id')
);
$installer->getConnection()->addIndex(
    $quoteTable,
    $this->getIdxName($quoteTable, array('quote_id')),
    array('quote_id')
);
$installer->getConnection()->addIndex(
    $quoteTable,
    $this->getIdxName($quoteTable, array('imported')),
    array('imported')
);
$installer->getConnection()->addIndex(
    $quoteTable,
    $this->getIdxName($quoteTable, array('modified')),
    array('modified')
);

/**
 * modify email_order table
 */
$orderTable = $installer->getTable('ddg_automation/order');

//modify column
$installer->getConnection()->modifyColumn(
    $orderTable, 'order_status', 'VARCHAR(50)'
);

//add column
$installer->getConnection()->addColumn(
    $orderTable, 'modified', array(
        'type'     => Varien_Db_Ddl_Table::TYPE_SMALLINT,
        'unsigned' => true,
        'nullable' => true,
        'default'  => null,
        'comment'  => 'Is Order Modified'
    )
);


//add indexes to order table
$installer->getConnection()->addIndex(
    $orderTable,
    $this->getIdxName($orderTable, array('email_imported')),
    array('email_imported')
);
$installer->getConnection()->addIndex(
    $orderTable,
    $this->getIdxName($orderTable, array('modified')),
    array('modified')
);

/**
 * modify email_review table
 */
$reviewTable = $installer->getTable('ddg_automation/review');

//add indexes to review table
$installer->getConnection()->addIndex(
    $reviewTable,
    $this->getIdxName($reviewTable, array('review_id')),
    array('review_id')
);
$installer->getConnection()->addIndex(
    $reviewTable,
    $this->getIdxName($reviewTable, array('customer_id')),
    array('customer_id')
);
$installer->getConnection()->addIndex(
    $reviewTable,
    $this->getIdxName($reviewTable, array('store_id')),
    array('store_id')
);
$installer->getConnection()->addIndex(
    $reviewTable,
    $this->getIdxName($reviewTable, array('review_imported')),
    array('review_imported')
);

/**
 * modify email_wishlist table
 */
$wishlistTable = $installer->getTable('ddg_automation/wishlist');

//add indexes to wishlist table
$installer->getConnection()->addIndex(
    $wishlistTable,
    $this->getIdxName($wishlistTable, array('wishlist_id')),
    array('wishlist_id')
);
$installer->getConnection()->addIndex(
    $wishlistTable,
    $this->getIdxName($wishlistTable, array('item_count')),
    array('item_count')
);
$installer->getConnection()->addIndex(
    $wishlistTable,
    $this->getIdxName($wishlistTable, array('customer_id')),
    array('customer_id')
);
$installer->getConnection()->addIndex(
    $wishlistTable,
    $this->getIdxName($wishlistTable, array('wishlist_modified')),
    array('wishlist_modified')
);
$installer->getConnection()->addIndex(
    $wishlistTable,
    $this->getIdxName($wishlistTable, array('wishlist_imported')),
    array('wishlist_imported')
);

//clear old config values
$conn  = $installer->getConnection();
$paths = array(
    'connector_sms%',
    'connector_advanced_settings%',
);

foreach ($paths as $path) {
    $expr = new Zend_Db_Expr("path LIKE '{$path}'");
    $conn->delete($this->getTable('core/config_data'), $expr);
}

$installer->endSetup();
