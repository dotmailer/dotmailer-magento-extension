<?php
//@codingStandardsIgnoreStart
/** @var Mage_Eav_Model_Entity_Setup $installer */

$installer = $this;
$installer->startSetup();


$configData = Mage::getModel('core/config_data')->getCollection()
    ->addFieldToFilter('path', Dotdigitalgroup_Email_Helper_Transactional::XML_PATH_DDG_TRANSACTIONAL_PASSWORD);
foreach ($configData as $config) {
    $value = $config->getValue();
    //pass value not empty
    if ($value) {
        $config->setValue(Mage::helper('core')->encrypt($value))
            ->save();
    }
}
//admin users token
$adminUsers = Mage::getModel('admin/user')->getCollection()
    ->addFieldToFilter('refresh_token', array('notnull' => true));
/** @var Mage_Admin_Model_User $adminUser */
foreach ($adminUsers as $adminUser) {
    $token = $adminUser->getRefreshToken();
    $adminUser->setRefreshToken(Mage::helper('core')->encrypt($token))
        ->save();
}

//clean the cache for config
Mage::getModel('core/config')->cleanCache();

$installer->endSetup();