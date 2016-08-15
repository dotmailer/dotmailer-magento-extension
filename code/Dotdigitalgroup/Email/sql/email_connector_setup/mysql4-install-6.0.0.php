<?php

/** @var Mage_Eav_Model_Entity_Setup $installer */

$installer = $this;
$installer->startSetup();

//Update contacts with customers that are subscribers
$select = $installer->getConnection()->select();

//join
$select->joinLeft(
    array('ns' => $installer->getTable('newsletter/subscriber')),
    "dc.customer_id = ns.customer_id",
    array(
        'is_subscriber' => new Zend_Db_Expr('1'),
        'subscriber_status' => new Zend_Db_Expr('1')
    )
)
    ->where('ns.subscriber_status =?', 1);

//update query from select
$contactTable = $installer->getTable('ddg_automation/contact');
$updateSql = $select->crossUpdateFromSelect(array('dc' => $contactTable));

//run query
$installer->getConnection()->query($updateSql);

$installer->endSetup();