<?php

/** @var Mage_Eav_Model_Entity_Setup $installer */

$installer = $this;
$installer->startSetup();

/**
 * create review table
 */
$reviewTable = $installer->getTable('ddg_automation/review');

//drop table if exist
if ($installer->getConnection()->isTableExists($reviewTable)) {
	$installer->getConnection()->dropTable($reviewTable);
}

$table = $installer->getConnection()->newTable($reviewTable);
$table->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
	'primary'  => true,
	'identity' => true,
	'unsigned' => true,
	'nullable' => false
), 'Primary Key')
      ->addColumn('review_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
	      'unsigned'  => true,
	      'nullable' => false,
      ), 'Review Id')
      ->addColumn('customer_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
	      'unsigned'  => true,
	      'nullable'  => false,
      ), 'Customer ID')
      ->addColumn('store_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
	      'unsigned'  => true,
	      'nullable'  => false,
      ), 'Store Id')
      ->addColumn('review_imported', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
	      'unsigned'  => true,
	      'nullable'  => true,
      ), 'Review Imported')
      ->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
      ), 'Creation Time')
      ->addColumn('updated_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
      ), 'Update Time')
      ->setComment('Connector Reviews');
$installer->getConnection()->createTable($table);

//populate review table
$inCond = $installer->getConnection()->prepareSqlCondition('review_detail.customer_id', array('notnull' => true));
$select = $installer->getConnection()->select()
                    ->from(
	                    array('review' => $this->getTable('review/review')),
	                    array('review_id' => 'review.review_id','created_at' => 'review.created_at')
                    )
                    ->joinLeft(
	                    array('review_detail' => $installer->getTable('review/review_detail')),
	                    "review_detail.review_id = review.review_id",
	                    array('store_id' => 'review_detail.store_id', 'customer_id' => 'review_detail.customer_id')
                    )
                    ->where($inCond);

$insertArray = array('review_id', 'created_at' ,'store_id', 'customer_id');
$sqlQuery = $select->insertFromSelect($reviewTable, $insertArray, false);
$installer->getConnection()->query($sqlQuery);

//add columns to table
$campaignTable = $installer->getTable('ddg_automation/campaign');

$installer->getConnection()->addColumn($campaignTable, 'from_address', array(
	'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
	'unsigned'  => true,
	'nullable' => true,
	'default' => null,
	'comment' => 'Email From Address'
));
$installer->getConnection()->addColumn($campaignTable, 'attachment_id', array(
	'type' => Varien_Db_Ddl_Table::TYPE_INTEGER,
	'unsigned' => true,
	'nullable' => true,
	'default' => null,
	'comment' => 'Attachment Id'
));

/**
 * create wishlist table
 */
$wishlistTable = $installer->getTable('ddg_automation/wishlist');

//drop table if exist
if ($installer->getConnection()->isTableExists($wishlistTable)) {
	$installer->getConnection()->dropTable($wishlistTable);
}

$table = $installer->getConnection()->newTable($wishlistTable);
$table->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
	'primary'  => true,
	'identity' => true,
	'unsigned' => true,
	'nullable' => false
), 'Primary Key')
      ->addColumn('wishlist_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
	      'unsigned'  => true,
	      'nullable' => false,
      ), 'Wishlist Id')
      ->addColumn('item_count', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
	      'unsigned'  => true,
	      'nullable' => false,
      ), 'Item Count')
      ->addColumn('customer_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
	      'unsigned'  => true,
	      'nullable'  => false,
      ), 'Customer ID')
      ->addColumn('store_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
	      'unsigned'  => true,
	      'nullable'  => false,
      ), 'Store Id')
      ->addColumn('wishlist_imported', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
	      'unsigned'  => true,
	      'nullable'  => true,
      ), 'Wishlist Imported')
      ->addColumn('wishlist_modified', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
	      'unsigned'  => true,
	      'nullable'  => true,
      ), 'Wishlist Modified')
      ->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
      ), 'Creation Time')
      ->addColumn('updated_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
      ), 'Update Time')
      ->setComment('Connector Wishlist');
$installer->getConnection()->createTable($table);

//wishlist populate
$select = $installer->getConnection()->select()
                    ->from(
	                    array('wishlist' => $installer->getTable('wishlist/wishlist')),
	                    array('wishlist_id', 'customer_id', 'created_at' => 'updated_at')
                    )->joinLeft(
		array('ce' => $installer->getTable('customer_entity')),
		"wishlist.customer_id = ce.entity_id",
		array('store_id')
	)->joinInner(
		array('wi' => $installer->getTable('wishlist_item')),
		"wishlist.wishlist_id = wi.wishlist_id",
		array('item_count' => 'count(wi.wishlist_id)')
	)->group('wi.wishlist_id');

$insertArray = array('wishlist_id' ,'customer_id','created_at', 'store_id', 'item_count');
$sqlQuery = $select->insertFromSelect($wishlistTable, $insertArray, false);
$installer->getConnection()->query($sqlQuery);

$installer->endSetup();