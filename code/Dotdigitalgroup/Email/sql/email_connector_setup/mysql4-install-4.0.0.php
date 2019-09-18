<?php
//@codingStandardsIgnoreStart
/** @var Mage_Eav_Model_Entity_Setup $installer */

$installer = $this;

$installer->startSetup();

/**
 * create Contact table.
 */
$contactTable = $installer->getTable('ddg_automation/contact');

if ($installer->getConnection()->isTableExists($contactTable)) {
    $installer->getConnection()->dropTable($contactTable);
}

$table = $installer->getConnection()->newTable($contactTable);
$table->addColumn(
    'email_contact_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
    'primary'  => true,
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
        'default'  => '0'
        ), 'Website ID'
    )
    ->addColumn(
        'store_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned' => true,
        'nullable' => false,
        'default'  => '0'
        ), 'Store ID'
    )
    ->addColumn(
        'email', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
        'nullable' => false,
        'default'  => ''
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
        $this->getIdxName($contactTable, array('email_contact_id')),
        array('email_contact_id')
    )
    ->addIndex(
        $this->getIdxName($contactTable, array('is_guest')),
        array('is_guest')
    )
    ->addIndex(
        $this->getIdxName($contactTable, array('customer_id')),
        array('customer_id')
    )
    ->addIndex(
        $this->getIdxName($contactTable, array('website_id')),
        array('website_id')
    )
    ->addIndex(
        $this->getIdxName($contactTable, array('is_subscriber')),
        array('is_subscriber')
    )
    ->addIndex(
        $this->getIdxName($contactTable, array('subscriber_status')),
        array('subscriber_status')
    )
    ->addIndex(
        $this->getIdxName($contactTable, array('email_imported')),
        array('email_imported')
    )
    ->addIndex(
        $this->getIdxName($contactTable, array('subscriber_imported')),
        array('subscriber_imported')
    )
    ->addIndex(
        $this->getIdxName($contactTable, array('suppressed')),
        array('suppressed')
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
 * Order table
 */
$orderTable = $installer->getTable('ddg_automation/order');

if ($installer->getConnection()->isTableExists($orderTable)) {
    $installer->getConnection()->dropTable($orderTable);
}

$table = $installer->getConnection()->newTable($orderTable);
$table->addColumn(
    'email_order_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
    'primary'  => true,
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
        'order_status', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
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
        'default'  => '0'
        ), 'Store ID'
    )
    ->addColumn(
        'email_imported', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'unsigned' => true,
        'nullable' => true,
        ), 'Is Order Imported'
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
        $this->getIdxName($orderTable, array('store_id')),
        array('store_id')
    )
    ->addIndex(
        $this->getIdxName($orderTable, array('quote_id')),
        array('quote_id')
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
 * Campaign table.
 */
$campaignTable = $installer->getTable('ddg_automation/campaign');

if ($installer->getConnection()->isTableExists($campaignTable)) {
    $installer->getConnection()->dropTable($campaignTable);
}

$table = $installer->getConnection()->newTable($campaignTable);
$table->addColumn(
    'id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
    'primary'  => true,
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
 * Admin notification message
 */
$adminData   = array();
$adminData[] = array(
    'severity'    => 4,
    'date_added'  => gmdate('Y-m-d H:i:s', time()),
    'title'       => 'Email Connector Was Installed. Please Enter Your API Credentials & Ensure Cron Jobs Are Running On Your Site (Find Out More)',
    'description' => 'Email Connector Was Installed. Please Enter Your API Credentials & Ensure Cron Jobs Are Running On Your Site.',
    'url'         => 'http://www.magentocommerce.com/wiki/1_-_installation_and_configuration/how_to_setup_a_cron_job'
);

Mage::getModel('adminnotification/inbox')->parse($adminData);

/**
 * Populate tables
 */
$select = $installer->getConnection()->select()
    ->from(
        array('customer' => $this->getTable('customer_entity')),
        array('customer_id' => 'entity_id', 'email', 'website_id', 'store_id')
    );

$insertArray = array('customer_id', 'email', 'website_id', 'store_id');
$sqlQuery    = $select->insertFromSelect($contactTable, $insertArray, false);
$installer->getConnection()->query($sqlQuery);

// subscribers that are not customers
$select      = $installer->getConnection()->select()
    ->from(
        array('subscriber' => $this->getTable('newsletter_subscriber')),
        array(
            'email' => 'subscriber_email',
            'col2'  => new Zend_Db_Expr('1'),
            'col3'  => new Zend_Db_Expr('1'),
            'store_id'
        )
    )
    ->where('customer_id =?', 0)
    ->where('subscriber_status =?', 1);
$insertArray = array('email', 'is_subscriber', 'subscriber_status', 'store_id');
$sqlQuery    = $select->insertFromSelect($contactTable, $insertArray, false);
$installer->getConnection()->query($sqlQuery);

//Insert and populate email order the table
$select      = $installer->getConnection()->select()
    ->from(
        $this->getTable('sales/order'),
        array('order_id' => 'entity_id', 'quote_id', 'store_id', 'created_at',
              'updated_at', 'order_status' => 'status')
    );
$insertArray = array('order_id', 'quote_id', 'store_id', 'created_at',
                     'updated_at', 'order_status');

$sqlQuery = $select->insertFromSelect($orderTable, $insertArray, false);
$installer->getConnection()->query($sqlQuery);

//Save all order statuses as string
$source   = Mage::getModel('adminhtml/system_config_source_order_status');
$statuses = $source->toOptionArray();

if (count($statuses) > 0 && $statuses[0]['value'] == '') {
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

$admin = $this->getTable('admin/user');
$installer->getConnection()->addColumn(
    $installer->getTable('admin/user'), 'refresh_token', array(
        'type'     => Varien_Db_Ddl_Table::TYPE_TEXT,
        'length'   => 256,
        'nullable' => true,
        'default'  => null,
        'comment'  => 'Email connector refresh token'
    )
);

$campaignTable = $this->getTable('ddg_automation/campaign');
$installer->getConnection()->modifyColumn(
    $campaignTable, 'order_increment_id', 'VARCHAR(50)'
);

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

//Save all order statuses as string to extension's config value
$source   = Mage::getModel('adminhtml/system_config_source_order_status');
$statuses = $source->toOptionArray();

if (count($statuses) > 0 && $statuses[0]['value'] == '') {
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

//add columns to table
$campaignTable = $installer->getTable('ddg_automation/campaign');
$installer->getConnection()->addColumn(
    $campaignTable, 'subject', array(
        'type'     => Varien_Db_Ddl_Table::TYPE_TEXT,
        'unsigned' => true,
        'nullable' => false,
        'default'  => '',
        'comment'  => 'Email Subject'
    )
);
$installer->getConnection()->addColumn(
    $campaignTable, 'html_content', array(
        'type'     => Varien_Db_Ddl_Table::TYPE_TEXT,
        'unsigned' => true,
        'nullable' => false,
        'default'  => '',
        'comment'  => 'Email Html Content'
    )
);
$installer->getConnection()->addColumn(
    $campaignTable, 'plain_text_content', array(
        'type'     => Varien_Db_Ddl_Table::TYPE_TEXT,
        'unsigned' => true,
        'nullable' => false,
        'default'  => '',
        'comment'  => 'Email Plain Text Content'
    )
);
$installer->getConnection()->addColumn(
    $campaignTable, 'from_name', array(
        'type'     => Varien_Db_Ddl_Table::TYPE_TEXT,
        'unsigned' => true,
        'nullable' => false,
        'default'  => '',
        'comment'  => 'Email From Name'
    )
);
$installer->getConnection()->addColumn(
    $campaignTable, 'create_message', array(
        'type'     => Varien_Db_Ddl_Table::TYPE_TEXT,
        'unsigned' => true,
        'nullable' => false,
        'default'  => '',
        'comment'  => 'Create Campaign Message'
    )
);
$installer->getConnection()->addColumn(
    $campaignTable, 'contact_message', array(
        'type'     => Varien_Db_Ddl_Table::TYPE_TEXT,
        'unsigned' => true,
        'nullable' => false,
        'default'  => '',
        'comment'  => 'Contact Message'
    )
);
$installer->getConnection()->addColumn(
    $campaignTable, 'is_created', array(
        'type'     => Varien_Db_Ddl_Table::TYPE_SMALLINT,
        'unsigned' => true,
        'nullable' => true,
        'comment'  => 'Is Campaign Created'
    )
);
$installer->getConnection()->addColumn(
    $campaignTable, 'is_copy', array(
        'type'     => Varien_Db_Ddl_Table::TYPE_SMALLINT,
        'unsigned' => true,
        'nullable' => true,
        'comment'  => 'Is Copy'
    )
);
$installer->getConnection()->addColumn(
    $campaignTable, 'type', array(
        'type'     => Varien_Db_Ddl_Table::TYPE_SMALLINT,
        'unsigned' => true,
        'nullable' => false,
        'default'  => '1',
        'comment'  => 'Type. 1: Campaign, 2: Create'
    )
);
$installer->getConnection()->addColumn(
    $campaignTable, 'website_id', array(
        'type'     => Varien_Db_Ddl_Table::TYPE_INTEGER,
        'unsigned' => true,
        'nullable' => false,
        'default'  => '0',
        'comment'  => 'Website Id'
    )
);


/**
 * create Config table.
 */
$configTable = $installer->getTable('ddg_automation/config');

if ($installer->getConnection()->isTableExists($configTable)) {
    $installer->getConnection()->dropTable($configTable);
}

$table = $installer->getConnection()->newTable($configTable);
$table->addColumn(
    'email_config_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
    'primary'  => true,
    'identity' => true,
    'unsigned' => true,
    'nullable' => false
    ), 'Primary Key'
)
    ->addColumn(
        'path', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable' => true,
        ), 'Config Path'
    )
    ->addColumn(
        'scope', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable' => true,
        ), 'Config Scope'
    )
    ->addColumn(
        'value', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
        'nullable' => false,
        'default'  => ''
        ), 'Config Value'
    )
    ->addColumn(
        'is_api', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'unsigned' => true,
        'nullable' => true,
        ), 'Only For Api Calls'
    )
    ->addColumn(
        'created_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(),
        'Creation Time'
    )
    ->addColumn(
        'updated_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(),
        'Update Time'
    )
    ->setComment('Connector Config Data');
$installer->getConnection()->createTable($table);

/**
 * create review table
 */
$reviewTable = $installer->getTable('ddg_automation/review');

//drop table if exist
if ($installer->getConnection()->isTableExists($reviewTable)) {
    $installer->getConnection()->dropTable($reviewTable);
}

$table = $installer->getConnection()->newTable($reviewTable);
$table->addColumn(
    'id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
    'primary'  => true,
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
    ->setComment('Connector Reviews');
$installer->getConnection()->createTable($table);

//populate review table
$inCond = $installer->getConnection()->prepareSqlCondition(
    'review_detail.customer_id', array('notnull' => true)
);
$select = $installer->getConnection()->select()
    ->from(
        array('review' => $this->getTable('review/review')),
        array('review_id'  => 'review.review_id',
              'created_at' => 'review.created_at')
    )
    ->joinLeft(
        array('review_detail' => $installer->getTable('review/review_detail')),
        "review_detail.review_id = review.review_id",
        array('store_id'    => 'review_detail.store_id',
              'customer_id' => 'review_detail.customer_id')
    )
    ->where($inCond);

$insertArray = array('review_id', 'created_at', 'store_id', 'customer_id');
$sqlQuery    = $select->insertFromSelect($reviewTable, $insertArray, false);
$installer->getConnection()->query($sqlQuery);

//add columns to table
$campaignTable = $installer->getTable('ddg_automation/campaign');
$installer->getConnection()->addColumn(
    $campaignTable, 'from_address', array(
        'type'     => Varien_Db_Ddl_Table::TYPE_TEXT,
        'unsigned' => true,
        'nullable' => true,
        'default'  => null,
        'comment'  => 'Email From Address'
    )
);
$installer->getConnection()->addColumn(
    $campaignTable, 'attachment_id', array(
        'type'     => Varien_Db_Ddl_Table::TYPE_INTEGER,
        'unsigned' => true,
        'nullable' => true,
        'default'  => null,
        'comment'  => 'Attachment Id'
    )
);

/**
 * create wishlist table
 */
$wishlistTable = $installer->getTable('ddg_automation/wishlist');
//drop table if exist
if ($installer->getConnection()->isTableExists($wishlistTable)) {
    $installer->getConnection()->dropTable($wishlistTable);
}

$table = $installer->getConnection()->newTable($wishlistTable);
$table->addColumn(
    'id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
    'primary'  => true,
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
    ->setComment('Connector Wishlist');
$installer->getConnection()->createTable($table);

//wishlist populate
$select = $installer->getConnection()->select()
    ->from(
        array('wishlist' => $installer->getTable('wishlist/wishlist')),
        array('wishlist_id', 'customer_id', 'created_at' => 'updated_at')
    )->joinLeft(
        array('ce' => $installer->getTable('customer_entity')),
        "wishlist.customer_id = ce.entity_id",
        array('store_id')
    )->joinInner(
        array('wi' => $installer->getTable('wishlist_item')),
        "wishlist.wishlist_id = wi.wishlist_id",
        array('item_count' => 'count(wi.wishlist_id)')
    )->group('wi.wishlist_id');

$insertArray = array('wishlist_id', 'customer_id', 'created_at', 'store_id',
                     'item_count');
$sqlQuery    = $select->insertFromSelect($wishlistTable, $insertArray, false);
$installer->getConnection()->query($sqlQuery);


/**
 * create quote table.
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
$sqlQuery    = $select->insertFromSelect($quoteTable, $insertArray, false);
$installer->getConnection()->query($sqlQuery);

/**
 * Enterprise customer segmentation.
 */
if (Mage::helper('ddg')->isEnterprise()) {
    //contact table
    $contactTable = $installer->getTable('ddg_automation/contact');
    //customer segment table
    $segmentTable = $installer->getTable('enterprise_customersegment/customer');
    //add additional column with segment ids
    $installer->getConnection()->addColumn(
        $contactTable,
        'segment_ids',
        'mediumtext'
    );

    //update contact table with customer segment ids
    $result = $installer->run(
        "update`{$contactTable}` c,(select customer_id, website_id, group_concat(`segment_id` separator ',') as segmentids
from `{$segmentTable}` group by customer_id) as s set c.segment_ids = segmentids, c.email_imported = null WHERE s.customer_id= c.customer_id and s.website_id = c.website_id"
    );
}

$campaignTable = $this->getTable('ddg_automation/campaign');

$installer->getConnection()->dropColumn($campaignTable, 'from_address');
$installer->getConnection()->dropColumn($campaignTable, 'attachment_id');
$installer->getConnection()->dropColumn($campaignTable, 'subject');
$installer->getConnection()->dropColumn($campaignTable, 'html_content');
$installer->getConnection()->dropColumn($campaignTable, 'plain_text_content');
$installer->getConnection()->dropColumn($campaignTable, 'from_name');
$installer->getConnection()->dropColumn($campaignTable, 'is_created');
$installer->getConnection()->dropColumn($campaignTable, 'is_copy');
$installer->getConnection()->dropColumn($campaignTable, 'type');
$installer->getConnection()->dropColumn($campaignTable, 'website_id');
$installer->getConnection()->dropColumn($campaignTable, 'create_message');
$installer->getConnection()->dropColumn($campaignTable, 'contact_message');

$installer->endSetup();

