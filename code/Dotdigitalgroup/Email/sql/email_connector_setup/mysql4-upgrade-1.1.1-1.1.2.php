<?php

$installer = $this;
$installer->startSetup();


$installer->addAttribute("order", "dotmailer_order_imported", array("type"=>"int"));

$installer->endSetup();