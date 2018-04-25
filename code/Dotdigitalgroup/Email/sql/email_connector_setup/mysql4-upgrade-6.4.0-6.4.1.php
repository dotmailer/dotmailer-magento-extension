<?php
//@codingStandardsIgnoreStart
/** @var Mage_Eav_Model_Entity_Setup $installer */

$installer = $this;
$installer->startSetup();

// Save config for allow non subscriber for features; AC and order review trigger campaign
$configModel = Mage::getModel('core/config');
//For AC
$configModel->saveConfig(
    Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CONTENT_ALLOW_NON_SUBSCRIBERS,
    1
);
//For order review
$configModel->saveConfig(
    Dotdigitalgroup_Email_Helper_Config::XML_PATH_REVIEW_ALLOW_NON_SUBSCRIBERS,
    1
);

//clean the cache for config
Mage::getModel('core/config')->cleanCache();

$installer->endSetup();