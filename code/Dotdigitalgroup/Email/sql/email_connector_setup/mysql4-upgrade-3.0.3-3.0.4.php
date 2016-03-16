<?php

$installer = $this;


$installer->startSetup();

$campaignTable = $this->getTable('email_campaign');

$installer->getConnection()->modifyColumn(
    $campaignTable, 'order_increment_id', 'VARCHAR(50)'
);

$installer->endSetup();