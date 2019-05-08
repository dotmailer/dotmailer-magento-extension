<?php
//@codingStandardsIgnoreStart
/** @var Mage_Eav_Model_Entity_Setup $installer */

$installer = $this;
$installer->startSetup();

$contactTable = $installer->getTable('ddg_automation/contact');
$table = $installer->getConnection()->addColumn(
    $contactTable, 'last_subscribed_at', array(
        'type' => Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
        'nullable' => true,
        'default' => null,
        'comment' => 'Last time contact subscribed',
    )
);

$installer->endSetup();