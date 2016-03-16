<?php

$installer = $this;
$installer->startSetup();

$entityTypeId     = $setup->getEntityTypeId('customer');
$attributeSetId   = $setup->getDefaultAttributeSetId($entityTypeId);
$attributeGroupId = $setup->getDefaultAttributeGroupId(
    $entityTypeId, $attributeSetId
);

$setup->addAttribute(
    'customer', 'dotmailer_contact_id', array(
        'input'        => 'text',
        'type'         => 'int',
        'label'        => 'Connector Contact ID',
        'visible'      => 1,
        'required'     => 0,
        'user_defined' => 0,
        ''
    )
);

$setup->addAttributeToGroup(
    $entityTypeId,
    $attributeSetId,
    $attributeGroupId,
    'dotmailer_contact_id',
    '999'  //sort_order
);

$oAttribute = Mage::getSingleton('eav/config')->getAttribute(
    'customer', 'dotmailer_contact_id'
);
$oAttribute->setData('used_in_forms', array('adminhtml_customer'));
$oAttribute->save();

$adminData   = array();
$adminData[] = array(
    'severity'    => 4,
    'date_added'  => gmdate('Y-m-d H:i:s', time()),
    'title'       => 'Email Connector Was Installed. Please Enter Your API Credentials & Ensure Cron Jobs Are Running On Your Site (Find Out More)',
    'description' => 'Email Connector Was Installed. Please Enter Your API Credentials & Ensure Cron Jobs Are Running On Your Site.',
    'url'         => 'http://www.magentocommerce.com/wiki/1_-_installation_and_configuration/how_to_setup_a_cron_job'
);

Mage::getModel('adminnotification/inbox')->parse($adminData);


$setup->endSetup();