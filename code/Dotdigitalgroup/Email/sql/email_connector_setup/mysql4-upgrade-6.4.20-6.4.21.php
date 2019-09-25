<?php
//@codingStandardsIgnoreStart
/** @var Mage_Eav_Model_Entity_Setup $installer */

$installer = $this;
$installer->startSetup();

$catalogTable = $installer->getTable('ddg_automation/catalog');

if ($installer->getConnection()->isTableExists($catalogTable)) {

    if ($installer->getConnection()->tableColumnExists($catalogTable, 'modified')) {

        $installer->getConnection()->dropIndex(
            $installer->getTable($catalogTable),
            'IDX_EMAIL_CATALOG_MODIFIED'
        );

        $installer->getConnection()->changeColumn($catalogTable, 'modified', 'last_imported_at', array(
            'type' => Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
            'nullable' => true,
            'default' => null,
            'comment' => 'Last imported date',
        ));

        $installer->getConnection()->addIndex($catalogTable,
            $installer->getIdxName($catalogTable, array('last_imported_at')),
            array('last_imported_at')
        );
    }

    if ($installer->getConnection()->tableColumnExists($catalogTable, 'imported')) {
        $installer->getConnection()->dropIndex(
            $installer->getTable($catalogTable),
            'IDX_EMAIL_CATALOG_IMPORTED'
        );

        $installer->getConnection()->changeColumn($catalogTable, 'imported', 'processed', array(
            'type' => Varien_Db_Ddl_Table::TYPE_SMALLINT,
            'unsigned' => true,
            'nullable' => false,
            'default' => 0,
            'comment' => 'Product processed'
        ));

        $installer->getConnection()->addIndex($catalogTable,
            $installer->getIdxName($catalogTable, array('processed')),
            array('processed')
        );
    }
}