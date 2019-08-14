<?php

/** @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

$installer->run("

ALTER TABLE `{$installer->getTable('neklo_monitor/report')}`
  CHANGE COLUMN `first_mtime` `first_time` INT(11) UNSIGNED NOT NULL,
  CHANGE COLUMN `last_mtime` `last_time` INT(11) UNSIGNED NOT NULL;

");

$installer->endSetup();