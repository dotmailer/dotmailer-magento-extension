<?php
//@codingStandardsIgnoreStart
/** @var Mage_Eav_Model_Entity_Setup $installer */

$installer = $this;
$installer->startSetup();

/**
 * Create contact table.
 */
$contactTable = $installer->getTable('ddg_automation/contact');

if ($installer->getConnection()->isTableExists($contactTable)) {
    $installer->getConnection()->dropTable($contactTable);
}

$table = $installer->getConnection()->newTable($contactTable);
$table->addColumn(
    'email_contact_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
    'primary' => true,
    'identity' => true,
    'unsigned' => true,
    'nullable' => false
), 'Primary Key'
)
    ->addColumn(
        'is_guest', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'unsigned' => true,
        'nullable' => true,
    ), 'Is Guest'
    )
    ->addColumn(
        'contact_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned' => true,
        'nullable' => true,
    ), 'Connector Contact ID'
    )
    ->addColumn(
        'customer_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned' => true,
        'nullable' => false,
    ), 'Customer ID'
    )
    ->addColumn(
        'website_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned' => true,
        'nullable' => false,
        'default' => '0'
    ), 'Website ID'
    )
    ->addColumn(
        'store_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned' => true,
        'nullable' => false,
        'default' => '0'
    ), 'Store ID'
    )
    ->addColumn(
        'email', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
        'nullable' => false,
        'default' => ''
    ), 'Customer Email'
    )
    ->addColumn(
        'is_subscriber', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'unsigned' => true,
        'nullable' => true,
    ), 'Is Subscriber'
    )
    ->addColumn(
        'subscriber_status', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'unsigned' => true,
        'nullable' => true,
    ), 'Subscriber status'
    )
    ->addColumn(
        'email_imported', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'unsigned' => true,
        'nullable' => true,
    ), 'Is Imported'
    )
    ->addColumn(
        'subscriber_imported', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'unsigned' => true,
        'nullable' => true,
    ), 'Subscriber Imported'
    )
    ->addColumn(
        'suppressed', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'unsigned' => true,
        'nullable' => true,
    ), 'Is Suppressed'
    )
    ->addIndex(
        $installer->getIdxName($contactTable, array('email_contact_id')),
        array('email_contact_id')
    )
    ->addIndex(
        $installer->getIdxName($contactTable, array('is_guest')),
        array('is_guest')
    )
    ->addIndex(
        $installer->getIdxName($contactTable, array('customer_id')),
        array('customer_id')
    )
    ->addIndex(
        $installer->getIdxName($contactTable, array('website_id')),
        array('website_id')
    )
    ->addIndex(
        $installer->getIdxName($contactTable, array('is_subscriber')),
        array('is_subscriber')
    )
    ->addIndex(
        $installer->getIdxName($contactTable, array('subscriber_status')),
        array('subscriber_status')
    )
    ->addIndex(
        $installer->getIdxName($contactTable, array('email_imported')),
        array('email_imported')
    )
    ->addIndex(
        $installer->getIdxName($contactTable, array('subscriber_imported')),
        array('subscriber_imported')
    )
    ->addIndex(
        $installer->getIdxName($contactTable, array('suppressed')),
        array('suppressed')
    )
    ->addIndex(
        $installer->getIdxName($contactTable, array('email')),
        array('email')
    )
    ->addIndex(
        $installer->getIdxName($contactTable, array('contact_id')),
        array('contact_id')
    )
    ->addForeignKey(
        $installer->getFkName(
            $contactTable, 'website_id', 'core/website', 'website_id'
        ),
        'website_id', $installer->getTable('core/website'), 'website_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE
    )
    ->setComment('Connector Contacts');
$installer->getConnection()->createTable($table);

/**
 * Create order table
 */
$orderTable = $installer->getTable('ddg_automation/order');

if ($installer->getConnection()->isTableExists($orderTable)) {
    $installer->getConnection()->dropTable($orderTable);
}

$table = $installer->getConnection()->newTable($orderTable);
$table->addColumn(
    'email_order_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
    'primary' => true,
    'identity' => true,
    'unsigned' => true,
    'nullable' => false
), 'Primary Key'
)
    ->addColumn(
        'order_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned' => true,
        'nullable' => false,
    ), 'Order ID'
    )
    ->addColumn(
        'order_status', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
        'unsigned' => true,
        'nullable' => false,
    ), 'Order Status'
    )
    ->addColumn(
        'quote_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned' => true,
        'nullable' => false,
    ), 'Sales Quote ID'
    )
    ->addColumn(
        'store_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned' => true,
        'nullable' => false,
        'default' => '0'
    ), 'Store ID'
    )
    ->addColumn(
        'email_imported', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'unsigned' => true,
        'nullable' => true,
    ), 'Is Order Imported'
    )
    ->addColumn(
        'modified', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'unsigned' => true,
        'nullable' => true,
    ), 'Is Order Modified'
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
        $installer->getIdxName($orderTable, array('order_id')),
        array('order_id')
    )
    ->addIndex(
        $installer->getIdxName($orderTable, array('store_id')),
        array('store_id')
    )
    ->addIndex(
        $installer->getIdxName($orderTable, array('quote_id')),
        array('quote_id')
    )
    ->addIndex(
        $installer->getIdxName($orderTable, array('email_imported')),
        array('email_imported')
    )
    ->addIndex(
        $installer->getIdxName($orderTable, array('order_status')),
        array('order_status')
    )
    ->addIndex(
        $installer->getIdxName($orderTable, array('modified')),
        array('modified')
    )
    ->addIndex(
        $installer->getIdxName($orderTable, array('updated_at')),
        array('updated_at')
    )
    ->addIndex(
        $installer->getIdxName($orderTable, array('created_at')),
        array('created_at')
    )
    ->addForeignKey(
        $installer->getFkName(
            $orderTable, 'store_id', 'core/store', 'store_id'
        ),
        'store_id', $installer->getTable('core/store'), 'store_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE
    )
    ->setComment('Transactional Orders Data');
$installer->getConnection()->createTable($table);

/**
 * Create campaign table.
 */
$campaignTable = $installer->getTable('ddg_automation/campaign');

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
        'default' => ''
    ), 'Contact Email'
    )
    ->addColumn(
        'customer_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned' => true,
        'nullable' => false,
    ), 'Customer ID'
    )
    ->addColumn(
        'sent_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(),
        'Send Date'
    )
    ->addColumn(
        'order_increment_id', Varien_Db_Ddl_Table::TYPE_VARCHAR, 50, array(
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
        'default' => ''
    ), 'Error Message'
    )
    ->addColumn(
        'checkout_method', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
        'nullable' => false,
        'default' => ''
    ), 'Checkout Method Used'
    )
    ->addColumn(
        'store_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned' => true,
        'nullable' => false,
        'default' => '0'
    ), 'Store ID'
    )
    ->addColumn(
        'event_name', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
        'nullable' => false,
        'default' => ''
    ), 'Event Name'
    )
    ->addColumn(
        'send_id', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
        'nullable' => false,
        'default' => ''
    ), 'Campaign send id'
    )
    ->addColumn(
        'send_status', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'nullable' => false,
        'default' => 0
    ), 'Send status'
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
        $installer->getIdxName($campaignTable, array('store_id')),
        array('store_id')
    )
    ->addIndex(
        $installer->getIdxName($campaignTable, array('campaign_id')),
        array('campaign_id')
    )
    ->addIndex(
        $installer->getIdxName($campaignTable, array('email')),
        array('email')
    )
    ->addIndex(
        $installer->getIdxName($campaignTable, array('send_id')),
        array('send_id')
    )
    ->addIndex(
        $installer->getIdxName($campaignTable, array('send_status')),
        array('send_status')
    )
    ->addIndex(
        $installer->getIdxName($campaignTable, array('created_at')),
        array('created_at')
    )
    ->addIndex(
        $installer->getIdxName($campaignTable, array('updated_at')),
        array('updated_at')
    )
    ->addIndex(
        $installer->getIdxName($campaignTable, array('sent_at')),
        array('sent_at')
    )
    ->addIndex(
        $installer->getIdxName($campaignTable, array('quote_id')),
        array('quote_id')
    )
    ->addIndex(
        $installer->getIdxName($campaignTable, array('event_name')),
        array('event_name')
    )
    ->addIndex(
        $installer->getIdxName($campaignTable, array('message')),
        array('message')
    )
    ->addIndex(
        $installer->getIdxName($campaignTable, array('customer_id')),
        array('customer_id')
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
 * Create review table
 */
$reviewTable = $installer->getTable('ddg_automation/review');

if ($installer->getConnection()->isTableExists($reviewTable)) {
    $installer->getConnection()->dropTable($reviewTable);
}

$table = $installer->getConnection()->newTable($reviewTable);
$table->addColumn(
    'id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
    'primary' => true,
    'identity' => true,
    'unsigned' => true,
    'nullable' => false
), 'Primary Key'
)
    ->addColumn(
        'review_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned' => true,
        'nullable' => false,
    ), 'Review Id'
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
        'review_imported', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'unsigned' => true,
        'nullable' => true,
    ), 'Review Imported'
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
        $installer->getIdxName($reviewTable, array('review_id')),
        array('review_id')
    )
    ->addIndex(
        $installer->getIdxName($reviewTable, array('customer_id')),
        array('customer_id')
    )
    ->addIndex(
        $installer->getIdxName($reviewTable, array('store_id')),
        array('store_id')
    )
    ->addIndex(
        $installer->getIdxName($reviewTable, array('review_imported')),
        array('review_imported')
    )
    ->addIndex(
        $installer->getIdxName($reviewTable, array('created_at')),
        array('created_at')
    )
    ->addIndex(
        $installer->getIdxName($reviewTable, array('updated_at')),
        array('updated_at')
    )
    ->setComment('Connector Reviews');
$installer->getConnection()->createTable($table);

/**
 * Create wishlist table
 */
$wishlistTable = $installer->getTable('ddg_automation/wishlist');

if ($installer->getConnection()->isTableExists($wishlistTable)) {
    $installer->getConnection()->dropTable($wishlistTable);
}

$table = $installer->getConnection()->newTable($wishlistTable);
$table->addColumn(
    'id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
    'primary' => true,
    'identity' => true,
    'unsigned' => true,
    'nullable' => false
), 'Primary Key'
)
    ->addColumn(
        'wishlist_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned' => true,
        'nullable' => false,
    ), 'Wishlist Id'
    )
    ->addColumn(
        'item_count', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned' => true,
        'nullable' => false,
    ), 'Item Count'
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
        'wishlist_imported', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'unsigned' => true,
        'nullable' => true,
    ), 'Wishlist Imported'
    )
    ->addColumn(
        'wishlist_modified', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'unsigned' => true,
        'nullable' => true,
    ), 'Wishlist Modified'
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
        $installer->getIdxName($wishlistTable, array('wishlist_id')),
        array('wishlist_id')
    )
    ->addIndex(
        $installer->getIdxName($wishlistTable, array('item_count')),
        array('item_count')
    )
    ->addIndex(
        $installer->getIdxName($wishlistTable, array('customer_id')),
        array('customer_id')
    )
    ->addIndex(
        $installer->getIdxName($wishlistTable, array('wishlist_modified')),
        array('wishlist_modified')
    )
    ->addIndex(
        $installer->getIdxName($wishlistTable, array('wishlist_imported')),
        array('wishlist_imported')
    )
    ->addIndex(
        $installer->getIdxName($wishlistTable, array('created_at')),
        array('created_at')
    )
    ->addIndex(
        $installer->getIdxName($wishlistTable, array('updated_at')),
        array('updated_at')
    )
    ->addIndex(
        $installer->getIdxName($wishlistTable, array('store_id')),
        array('store_id')
    )
    ->setComment('Connector Wishlist');
$installer->getConnection()->createTable($table);

/**
 * Create quote table.
 */
$quoteTable = $installer->getTable('ddg_automation/quote');

if ($installer->getConnection()->isTableExists($quoteTable)) {
    $installer->getConnection()->dropTable($quoteTable);
}

$table = $installer->getConnection()->newTable($quoteTable);
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
        'created_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(),
        'Creation Time'
    )
    ->addColumn(
        'updated_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(),
        'Update Time'
    )
    ->addIndex(
        $installer->getIdxName($quoteTable, array('quote_id')),
        array('quote_id')
    )
    ->addIndex(
        $installer->getIdxName($quoteTable, array('customer_id')),
        array('customer_id')
    )
    ->addIndex(
        $installer->getIdxName($quoteTable, array('store_id')),
        array('store_id')
    )
    ->addIndex(
        $installer->getIdxName($quoteTable, array('imported')),
        array('imported')
    )
    ->addIndex(
        $installer->getIdxName($quoteTable, array('modified')),
        array('modified')
    )
    ->addIndex(
        $installer->getIdxName($quoteTable, array('created_at')),
        array('created_at')
    )
    ->addIndex(
        $installer->getIdxName($quoteTable, array('updated_at')),
        array('updated_at')
    )
    ->setComment('Connector Quotes');
$installer->getConnection()->createTable($table);

/**
 * Create automation table.
 */
$automationTable = $installer->getTable('ddg_automation/automation');

if ($installer->getConnection()->isTableExists($automationTable)) {
    $installer->getConnection()->dropTable($automationTable);
}

$table = $installer->getConnection()->newTable($automationTable);
$table->addColumn(
    'id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
    'primary' => true,
    'identity' => true,
    'unsigned' => true,
    'nullable' => false
), 'Primary Key'
)
    ->addColumn(
        'automation_type', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable' => true,
    ), 'Automation Type'
    )
    ->addColumn(
        'store_name', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable' => true,
    ), 'Store Name'
    )
    ->addColumn(
        'enrolment_status', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable' => false,
    ), 'Entrolment Status'
    )
    ->addColumn(
        'email', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable' => true,
    ), 'Email'
    )
    ->addColumn(
        'type_id', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable' => true,
    ), 'Type ID'
    )
    ->addColumn(
        'program_id', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable' => true,
    ), 'Program ID'
    )
    ->addColumn(
        'website_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned' => true,
        'nullable' => false,
    ), 'Website Id'
    )
    ->addColumn(
        'message', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable' => false,
    ), 'Message'
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
        $installer->getIdxName($automationTable, array('automation_type')),
        array('automation_type')
    )->addIndex(
        $installer->getIdxName($automationTable, array('enrolment_status')),
        array('enrolment_status')
    )
    ->addIndex(
        $installer->getIdxName($automationTable, array('type_id')),
        array('type_id')
    )
    ->addIndex(
        $installer->getIdxName($automationTable, array('email')),
        array('email')
    )
    ->addIndex(
        $installer->getIdxName($automationTable, array('program_id')),
        array('program_id')
    )
    ->addIndex(
        $installer->getIdxName($automationTable, array('created_at')),
        array('created_at')
    )
    ->addIndex(
        $installer->getIdxName($automationTable, array('updated_at')),
        array('updated_at')
    )
    ->addIndex(
        $installer->getIdxName($automationTable, array('website_id')),
        array('website_id')
    )
    ->setComment('Automation Status');
$installer->getConnection()->createTable($table);

/**
 * Create catalog table.
 */
$catalogTable = $installer->getTable('ddg_automation/catalog');

if ($installer->getConnection()->isTableExists($catalogTable)) {
    $installer->getConnection()->dropTable($catalogTable);
}

$table = $installer->getConnection()->newTable($catalogTable);
$table->addColumn(
    'id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
    'primary' => true,
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
        $installer->getIdxName($catalogTable, array('product_id')),
        array('product_id')
    )
    ->addIndex(
        $installer->getIdxName($catalogTable, array('imported')),
        array('imported')
    )
    ->addIndex(
        $installer->getIdxName($catalogTable, array('modified')),
        array('modified')
    )
    ->addIndex(
        $installer->getIdxName($catalogTable, array('created_at')),
        array('created_at')
    )
    ->addIndex(
        $installer->getIdxName($catalogTable, array('updated_at')),
        array('updated_at')
    )
    ->setComment('Connector Catalog');
$installer->getConnection()->createTable($table);

/**
 * Create rules table.
 */
$rulesTable = $installer->getTable('ddg_automation/rules');

if ($installer->getConnection()->isTableExists($rulesTable)) {
    $installer->getConnection()->dropTable($rulesTable);
}

$table = $installer->getConnection()->newTable($rulesTable);
$table->addColumn(
    'id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
    'primary' => true,
    'identity' => true,
    'unsigned' => true,
    'nullable' => false
), 'Primary Key'
)
    ->addColumn(
        'name', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
        'nullable' => false,
        'default' => ''
    ), 'Rule Name'
    )
    ->addColumn(
        'website_ids', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
        'nullable' => false,
        'default' => '0'
    ), 'Website Id'
    )
    ->addColumn(
        'type', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'nullable' => false,
        'default' => 0
    ), 'Rule Type'
    )
    ->addColumn(
        'status', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'nullable' => false,
        'default' => 0
    ), 'Status'
    )
    ->addColumn(
        'combination', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'nullable' => false,
        'default' => '1'
    ), 'Rule Condition'
    )
    ->addColumn(
        'condition', Varien_Db_Ddl_Table::TYPE_BLOB, null, array(
        'nullable' => false,
        'default' => ''
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

/**
 * Create email importer table.
 */
$importerTable = $installer->getTable('ddg_automation/importer');

if ($installer->getConnection()->isTableExists($importerTable)) {
    $installer->getConnection()->dropTable($importerTable);
}

$table = $installer->getConnection()->newTable($importerTable);
$table->addColumn(
    'id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
    'primary' => true,
    'identity' => true,
    'unsigned' => true,
    'nullable' => false
), 'Primary Key'
)
    ->addColumn(
        'import_type', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
        'nullable' => false,
        'default' => ''
    ), 'Import Type'
    )
    ->addColumn(
        'website_id', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
        'nullable' => false,
        'default' => '0'
    ), 'Website Id'
    )
    ->addColumn(
        'import_status', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'nullable' => false,
        'default' => 0
    ), 'Import Status'
    )
    ->addColumn(
        'import_id', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
        'nullable' => false,
        'default' => ''
    ), 'Import Id'
    )
    ->addColumn(
        'import_data', Varien_Db_Ddl_Table::TYPE_BLOB, '2M', array(
        'nullable' => false,
        'default' => ''
    ), 'Import Data'
    )
    ->addColumn(
        'import_mode', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
        'nullable' => false,
        'default' => ''
    ), 'Import Mode'
    )
    ->addColumn(
        'import_file', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        'nullable' => false,
        'default' => ''
    ), 'Import File'
    )
    ->addColumn(
        'message', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
        'nullable' => false,
        'default' => ''
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
        $installer->getIdxName($importerTable, array('import_type')),
        array('import_type')
    )
    ->addIndex(
        $installer->getIdxName($importerTable, array('website_id')),
        array('website_id')
    )
    ->addIndex(
        $installer->getIdxName($importerTable, array('import_status')),
        array('import_status')
    )
    ->addIndex(
        $installer->getIdxName($importerTable, array('import_mode')),
        array('import_mode')
    )
    ->addIndex(
        $installer->getIdxName($importerTable, array('created_at')),
        array('created_at')
    )
    ->addIndex(
        $installer->getIdxName($importerTable, array('updated_at')),
        array('updated_at')
    )
    ->addIndex(
        $installer->getIdxName($importerTable, array('import_id')),
        array('import_id')
    )
    ->addIndex(
        $installer->getIdxName($importerTable, array('import_started')),
        array('import_started')
    )
    ->addIndex(
        $installer->getIdxName($importerTable, array('import_finished')),
        array('import_finished')
    )
    ->setComment('Email Importer');
$installer->getConnection()->createTable($table);

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
        $installer->getIdxName($contactTable, array('quote_id')),
        array('quote_id'),
        array('type' => 'unique')
    )
    ->addIndex(
        $installer->getIdxName($contactTable, array('store_id')),
        array('store_id')
    )
    ->addIndex(
        $installer->getIdxName($contactTable, array('customer_id')),
        array('customer_id')
    )
    ->addIndex(
        $installer->getIdxName($contactTable, array('email')),
        array('email')
    )
    ->addForeignKey(
        $installer->getFkName(
            $contactTable, 'quote_id', 'sales/quote', 'entity_id'
        ),
        'quote_id', $installer->getTable('sales/quote'), 'entity_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE
    )
    ->setComment('Abandoned Carts');
$installer->getConnection()->createTable($table);

/**
 * Add column to coupon table
 */
$installer->getConnection()->addColumn(
    $installer->getTable('salesrule/coupon'), 'generated_by_dotmailer', array(
        'type' => Varien_Db_Ddl_Table::TYPE_SMALLINT,
        'nullable' => true,
        'default' => null,
        'comment' => '1 = Generated by dotmailer',
    )
);

/**
 * Add column to admin_user table
 */
$admin = $installer->getTable('admin/user');
$installer->getConnection()->addColumn(
    $installer->getTable('admin/user'), 'refresh_token', array(
        'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
        'length' => 256,
        'nullable' => true,
        'default' => null,
        'comment' => 'Email connector refresh token'
    )
);

/**
 * Enterprise customer segmentation.
 */
if (Mage::helper('ddg')->isEnterprise()) {
    //add additional column with segment ids
    $installer->getConnection()->addColumn(
        $contactTable,
        'segment_ids',
        'mediumtext'
    );
}

$installer->endSetup();
