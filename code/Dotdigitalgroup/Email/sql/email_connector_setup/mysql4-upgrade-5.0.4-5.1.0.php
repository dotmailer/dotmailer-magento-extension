<?php

/** @var Mage_Eav_Model_Entity_Setup $installer */

$installer = $this;
$installer->startSetup();

/**
 * modify email_contact table
 */
$contactTable = $installer->getTable('ddg_automation/contact');

//add indexes to table
$installer->getConnection()->addIndex(
    $contactTable,
    $installer->getIdxName($contactTable, array('email')),
    array('email')
);

/**
 * modify email_automation table
 */
$automationTable = $installer->getTable('ddg_automation/automation');

//add indexes to table
$installer->getConnection()->addIndex(
    $automationTable,
    $installer->getIdxName($automationTable, array('type_id')),
    array('type_id')
);
$installer->getConnection()->addIndex(
    $automationTable,
    $installer->getIdxName($automationTable, array('automation_type')),
    array('automation_type')
);
$installer->getConnection()->addIndex(
    $automationTable,
    $installer->getIdxName($automationTable, array('email')),
    array('email')
);
$installer->getConnection()->addIndex(
    $automationTable,
    $installer->getIdxName($automationTable, array('program_id')),
    array('program_id')
);
$installer->getConnection()->addIndex(
    $automationTable,
    $installer->getIdxName($automationTable, array('message')),
    array('message')
);
$installer->getConnection()->addIndex(
    $automationTable,
    $installer->getIdxName($automationTable, array('created_at')),
    array('created_at')
);
$installer->getConnection()->addIndex(
    $automationTable,
    $installer->getIdxName($automationTable, array('updated_at')),
    array('updated_at')
);
$installer->getConnection()->addIndex(
    $automationTable,
    $installer->getIdxName($automationTable, array('website_id')),
    array('website_id')
);

/**
 * modify email_order table
 */
$orderTable = $installer->getTable('ddg_automation/order');

//add indexes to table
$installer->getConnection()->addIndex(
    $orderTable,
    $installer->getIdxName($orderTable, array('updated_at')),
    array('updated_at')
);
$installer->getConnection()->addIndex(
    $orderTable,
    $installer->getIdxName($orderTable, array('created_at')),
    array('created_at')
);

/**
 * modify email_quote table
 */
$quoteTable = $installer->getTable('ddg_automation/quote');

//add indexes to table
$installer->getConnection()->addIndex(
    $quoteTable,
    $installer->getIdxName($quoteTable, array('created_at')),
    array('created_at')
);
$installer->getConnection()->addIndex(
    $quoteTable,
    $installer->getIdxName($quoteTable, array('updated_at')),
    array('updated_at')
);

/**
 * modify email_review table
 */
$reviewTable = $installer->getTable('ddg_automation/review');

//add indexes to table
$installer->getConnection()->addIndex(
    $reviewTable,
    $installer->getIdxName($reviewTable, array('created_at')),
    array('created_at')
);
$installer->getConnection()->addIndex(
    $reviewTable,
    $installer->getIdxName($reviewTable, array('updated_at')),
    array('updated_at')
);

/**
 * modify email_wishlist table
 */
$wishlistTable = $installer->getTable('ddg_automation/wishlist');

//add indexes to table
$installer->getConnection()->addIndex(
    $wishlistTable,
    $installer->getIdxName($wishlistTable, array('created_at')),
    array('created_at')
);
$installer->getConnection()->addIndex(
    $wishlistTable,
    $installer->getIdxName($wishlistTable, array('updated_at')),
    array('updated_at')
);

/**
 * modify email_catalog table
 */
$catalogTable = $installer->getTable('ddg_automation/catalog');

//add indexes to table
$installer->getConnection()->addIndex(
    $catalogTable,
    $installer->getIdxName($catalogTable, array('created_at')),
    array('created_at')
);
$installer->getConnection()->addIndex(
    $catalogTable,
    $installer->getIdxName($catalogTable, array('updated_at')),
    array('updated_at')
);

/**
 * modify email_importer table
 */
$importerTable = $installer->getTable('ddg_automation/importer');

//add indexes to table
$installer->getConnection()->addIndex(
    $importerTable,
    $installer->getIdxName($importerTable, array('created_at')),
    array('created_at')
);
$installer->getConnection()->addIndex(
    $importerTable,
    $installer->getIdxName($importerTable, array('updated_at')),
    array('updated_at')
);
$installer->getConnection()->addIndex(
    $importerTable,
    $installer->getIdxName($importerTable, array('message')),
    array('message')
);
$installer->getConnection()->addIndex(
    $importerTable,
    $installer->getIdxName($importerTable, array('import_started')),
    array('import_started')
);
$installer->getConnection()->addIndex(
    $importerTable,
    $installer->getIdxName($importerTable, array('import_finished')),
    array('import_finished')
);

/**
 * modify email_campaign table
 */
$campaignTable = $installer->getTable('ddg_automation/campaign');

//add indexes to table
$installer->getConnection()->addIndex(
    $campaignTable,
    $installer->getIdxName($campaignTable, array('created_at')),
    array('created_at')
);
$installer->getConnection()->addIndex(
    $campaignTable,
    $installer->getIdxName($campaignTable, array('updated_at')),
    array('updated_at')
);
$installer->getConnection()->addIndex(
    $campaignTable,
    $installer->getIdxName($campaignTable, array('sent_at')),
    array('sent_at')
);
$installer->getConnection()->addIndex(
    $campaignTable,
    $installer->getIdxName($campaignTable, array('quote_id')),
    array('quote_id')
);
$installer->getConnection()->addIndex(
    $campaignTable,
    $installer->getIdxName($campaignTable, array('event_name')),
    array('event_name')
);
$installer->getConnection()->addIndex(
    $campaignTable,
    $installer->getIdxName($campaignTable, array('message')),
    array('message')
);
$installer->getConnection()->addIndex(
    $campaignTable,
    $installer->getIdxName($campaignTable, array('customer_id')),
    array('customer_id')
);

//if default 'manufacturer' attribute found save it in config
$attributes = Mage::getResourceModel('catalog/product_attribute_collection')->addVisibleFilter();
$attributes->addFieldToFilter('main_table.attribute_code', 'manufacturer');
if($attributes->getSize()){
    $configModel = Mage::getModel('core/config');
    $configModel->saveConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_DATA_FIELDS_BRAND_ATTRIBUTE, 'manufacturer');
}


$installer->endSetup();