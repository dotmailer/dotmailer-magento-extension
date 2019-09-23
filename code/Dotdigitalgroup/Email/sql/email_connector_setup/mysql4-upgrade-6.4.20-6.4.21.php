<?php
//@codingStandardsIgnoreStart
/** @var Mage_Eav_Model_Entity_Setup $installer */

$installer = $this;
$installer->startSetup();

$catalogTable = $installer->getTable('ddg_automation/catalog');

if ($installer->getConnection()->isTableExists($catalogTable)) {
    $installer->getConnection()->changeColumn($catalogTable, 'modified','last_imported_at',array(
            'type' => Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
            'nullable' => true,
            'default' => null,
            'comment' => 'Last imported date',
        ));

    $installer->getConnection()->dropIndex(
        $installer->getTable($catalogTable),
        'IDX_EMAIL_CATALOG_IMPORTED'
    );

    $installer->getConnection()->dropIndex(
        $installer->getTable($catalogTable),
        'IDX_EMAIL_CATALOG_MODIFIED'
    );

    $installer->getConnection()->changeColumn($catalogTable, 'imported', 'processed', array(
        'type' => Varien_Db_Ddl_Table::TYPE_SMALLINT,
        'unsigned' => true,
        'nullable' => false,
        'comment' => 'Product processed'
    ));

    $installer->getConnection()->addIndex($catalogTable,
        $installer->getIdxName($catalogTable, array('processed')),
        array('processed')
    );

    $installer->getConnection()->addIndex($catalogTable,
        $installer->getIdxName($catalogTable, array('last_imported_at')),
        array('last_imported_at')
    );
}