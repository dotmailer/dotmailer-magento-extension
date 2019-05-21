<?php
//@codingStandardsIgnoreStart
/** @var Mage_Eav_Model_Entity_Setup $installer */

$installer = $this;
$installer->startSetup();

$contactTable = $installer->getTable('ddg_automation/contact');
if ($installer->getConnection()->isTableExists($contactTable)) {
    $installer->getConnection()->addColumn(
        $contactTable,
        'last_subscribed_at',
        array(
            'type' => Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
            'nullable' => true,
            'default' => null,
            'comment' => 'Last time contact subscribed',
        )
    );
}

$orderTable = $installer->getTable('ddg_automation/order');
if ($installer->getConnection()->isTableExists($orderTable)) {
    $installer->getConnection()->addIndex(
        $orderTable,
        'IDX_EMAIL_ORDER_ORDER_ID',
        array('order_id'));
}

$installer->endSetup();