<?php
//@codingStandardsIgnoreStart
/** @var Mage_Eav_Model_Entity_Setup $installer */

$installer = $this;
$installer->startSetup();

/**
 * Enterprise customer segmentation.
 */
if (Mage::helper('ddg')->isEnterprise()) {
    //contact table
    $contactTable = $installer->getTable('ddg_automation/contact');
    //customer segment table
    $segmentTable = $installer->getTable('enterprise_customersegment/customer');
    //add additional column with segment ids
    $installer->getConnection()
        ->addColumn(
            $contactTable,
            'segment_ids',
            'mediumtext'
        );
    //update contact table with customer segment ids
    $result = $installer->run(
        "update`{$contactTable}` c,(select customer_id, website_id, group_concat(`segment_id` separator ',') as 
        segmentids from `{$segmentTable}` group by customer_id) as s set c.segment_ids = segmentids, 
        c.email_imported = null
        WHERE s.customer_id= c.customer_id and s.website_id = c.website_id"
    );
}

$campaignTable = $this->getTable('ddg_automation/campaign');

$installer->getConnection()->dropColumn($campaignTable, 'from_address');
$installer->getConnection()->dropColumn($campaignTable, 'attachment_id');
$installer->getConnection()->dropColumn($campaignTable, 'subject');
$installer->getConnection()->dropColumn($campaignTable, 'html_content');
$installer->getConnection()->dropColumn($campaignTable, 'plain_text_content');
$installer->getConnection()->dropColumn($campaignTable, 'from_name');
$installer->getConnection()->dropColumn($campaignTable, 'is_created');
$installer->getConnection()->dropColumn($campaignTable, 'is_copy');
$installer->getConnection()->dropColumn($campaignTable, 'type');
$installer->getConnection()->dropColumn($campaignTable, 'website_id');
$installer->getConnection()->dropColumn($campaignTable, 'create_message');
$installer->getConnection()->dropColumn($campaignTable, 'contact_message');

$installer->endSetup();