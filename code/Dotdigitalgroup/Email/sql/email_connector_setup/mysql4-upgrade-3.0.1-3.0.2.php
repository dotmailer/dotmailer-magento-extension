<?php

$installer = $this;


$installer->startSetup();

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

$installer->endSetup();