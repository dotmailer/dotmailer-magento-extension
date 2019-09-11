<?php
//@codingStandardsIgnoreStart
/** @var Mage_Eav_Model_Entity_Setup $installer */

$installer = $this;

$installer->startSetup();

/**
 * create Contact table.
 */
$contactTable = $installer->getTable('email_connector/contact');

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
$orderTable = $installer->getTable('email_connector/order');

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
$campaignTable = $installer->getTable('email_connector/campaign');

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

//customers populate
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

$installer->endSetup();
