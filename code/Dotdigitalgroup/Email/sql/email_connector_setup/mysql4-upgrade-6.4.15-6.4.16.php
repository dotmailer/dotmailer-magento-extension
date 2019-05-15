<?php
//@codingStandardsIgnoreStart
/** @var Mage_Eav_Model_Entity_Setup $installer */

$installer = $this;
$installer->startSetup();

$orderTable = $installer->getTable('ddg_automation/order');

if ($installer->getConnection()->isTableExists($orderTable)) {
    $installer->getConnection()->addIndex(
        $orderTable,
        'IDX_EMAIL_ORDER_ORDER_ID',
        array('order_id'));
}

$installer->endSetup();
