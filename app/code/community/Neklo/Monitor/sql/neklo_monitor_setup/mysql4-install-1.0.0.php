<?php

/** @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();
$tbl = $installer->getTable('neklo_monitor/report');
$installer->run("

CREATE TABLE `$tbl` (
  `report_id` INT(11) NOT NULL AUTO_INCREMENT,
  `last_mtime` INT(11) UNSIGNED NOT NULL,
  `qty` INT(11) unsigned NOT NULL,
  `message` TEXT NOT NULL,
  `hash` VARCHAR(32) NOT NULL,
  `files` TEXT NOT NULL,
  PRIMARY KEY (`report_id`),
  KEY `hash` (`hash`),
  KEY `last_mtime` (`last_mtime`)
) ENGINE=InnoDB;

");

$installer->endSetup();
