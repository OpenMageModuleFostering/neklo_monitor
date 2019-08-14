<?php

/** @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();
$tbl = $installer->getTable('neklo_monitor/log');
$installer->run("

CREATE TABLE `$tbl` (
  `log_id` INT(11) NOT NULL AUTO_INCREMENT,
  `type` VARCHAR(10) NOT NULL,
  `last_time` INT(11) UNSIGNED NOT NULL,
  `qty` INT(11) unsigned NOT NULL,
  `message` TEXT NOT NULL,
  `hash` VARCHAR(32) NOT NULL,
  `times` TEXT NOT NULL,
  PRIMARY KEY (`log_id`),
  KEY `hash` (`hash`),
  KEY `last_time` (`last_time`),
  KEY `type` (`type`)
) ENGINE=InnoDB;

");

$installer->endSetup();
