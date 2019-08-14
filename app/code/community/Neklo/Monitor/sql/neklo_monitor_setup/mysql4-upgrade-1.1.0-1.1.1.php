<?php

/** @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

$installer->run("

ALTER TABLE `{$installer->getTable('neklo_monitor/report')}`
  ADD COLUMN `first_mtime` INT(11) UNSIGNED NOT NULL AFTER `report_id`;

ALTER TABLE `{$installer->getTable('neklo_monitor/log')}`
  ADD COLUMN `first_time` INT(11) UNSIGNED NOT NULL AFTER `type`;

");

$installer->endSetup();