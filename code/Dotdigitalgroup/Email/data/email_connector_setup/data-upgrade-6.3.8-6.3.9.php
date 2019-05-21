<?php

/** @var Dotdigitalgroup_Email_Helper_Setup $setupHelper */
$setupHelper = Mage::helper('ddg/setup');
if ($setupHelper->skipMigrateData()) {
    return;
}

$setupHelper->encryptApiPasswordAndUserToken();