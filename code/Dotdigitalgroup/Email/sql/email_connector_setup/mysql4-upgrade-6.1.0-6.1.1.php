<?php

/** @var Mage_Eav_Model_Entity_Setup $installer */

$installer = $this;
$installer->startSetup();

/**
 * modify email_campaign table
 */
$campaignTable = $installer->getTable('ddg_automation/campaign');

//add column
$installer->getConnection()->addColumn(
    $campaignTable, 'send_id', array(
        'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
        'nullable' => false,
        'default' => '',
        'comment' => 'Campaign Send Id'
    )
);

$installer->endSetup();