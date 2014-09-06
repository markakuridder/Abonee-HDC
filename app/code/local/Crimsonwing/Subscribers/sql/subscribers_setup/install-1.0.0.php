<?php

/** @var Mage_Catalog_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

$installer->run("
    CREATE TABLE IF NOT EXISTS `" . $installer->getTable('subscribers/code') . "` (
      `code_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `code` varchar(50) NOT NULL,
      `created_at` datetime NOT NULL,
      `assigned_at` datetime NULL,
      `subscriber_number` int(10) unsigned NULL,
      `subscriber_email` varchar(100) NULL,
      `subscriber_name` varchar(100) NULL,
      PRIMARY KEY (`code_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
");

$installer->endSetup();
