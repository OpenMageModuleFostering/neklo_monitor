<?php

/** @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();
$tbl = $installer->getTable('neklo_monitor/queue');
$installer->run("

CREATE TABLE `$tbl` (
  `queue_id` INT(11) NOT NULL AUTO_INCREMENT,
  `type` VARCHAR(32) NOT NULL,
  `scheduled_at` INT(11) UNSIGNED NOT NULL,
  `started_at` INT(11) UNSIGNED NOT NULL,
  `sent_at` INT(11) UNSIGNED NOT NULL,
  `message` TEXT NOT NULL,
  PRIMARY KEY (`queue_id`),
  KEY `started_at` (`started_at`),
  KEY `type` (`type`)
) ENGINE=InnoDB;

");

$installer->endSetup();