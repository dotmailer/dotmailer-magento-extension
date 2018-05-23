<?php
//@codingStandardsIgnoreStart
/** @var Mage_Eav_Model_Entity_Setup $installer */

$installer = $this;
$installer->startSetup();

$consentTable = $installer->getTable('ddg_automation/consent');

if ($installer->getConnection()->isTableExists($consentTable)) {
    $installer->getConnection()->dropTable($consentTable);
}

$table = $installer->getConnection()->newTable($consentTable);
$table->addColumn(
    'id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
    'primary' => true,
    'identity' => true,
    'unsigned' => true,
    'nullable' => false
), 'Primary Key'
)
    ->addColumn(
        'email_contact_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned' => true,
        'nullable' => true,
    ), 'Email Contact Id'
    )
    ->addColumn(
        'consent_url', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'unsigned' => true,
        'nullable' => true,
    ), 'Contact consent url'
    )
    ->addColumn(
        'consent_datetime', Varien_Db_Ddl_Table::TYPE_DATETIME, null, array(),
        'Contact consent datetime'
    )
    ->addColumn(
        'consent_ip', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'unsigned' => true,
        'nullable' => true,
    ), 'Contact consent Ip'
    )
    ->addColumn(
        'consent_user_agent', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'unsigned' => true,
        'nullable' => true,
    ), 'Consent user agent'
    )
    ->addIndex(
        $installer->getIdxName($consentTable, array('email_contact_id')),
        array('email_contact_id'),
        array('type' => 'unique')
    )
    ->addForeignKey(
        $installer->getFkName(
            $consentTable, 'email_contact_id', 'ddg_automation/contact', 'email_contact_id'
        ),
        'email_contact_id',
        $installer->getTable('ddg_automation/contact'),
        'email_contact_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE
    )
    ->addIndex(
        $installer->getIdxName($consentTable, array('email_contact_id')),
        array('email_contact_id')
    )
    ->setComment('Email contact consent table');
$installer->getConnection()->createTable($table);

$installer->endSetup();