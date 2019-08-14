<?php

/** @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();
$cltbl = $installer->getTable('neklo_monitor/cicl');
$citbl = $installer->getTable('cataloginventory_stock_status');

$installer->run("

CREATE TABLE `$cltbl` (
    `cl_id` INT(11) NOT NULL AUTO_INCREMENT,
    `product_id` INT(11) UNSIGNED NOT NULL,
    `created_at` INT(11) UNSIGNED NOT NULL,
    PRIMARY KEY (`cl_id`),
    KEY `product_id` (`product_id`)
) ENGINE=InnoDB Comment='Catalog Inventory Status Change Log';

CREATE TRIGGER `neklo_monitor_inventory_insert`
AFTER INSERT ON `$citbl`
FOR EACH ROW
    INSERT INTO `$cltbl` (`product_id`, `created_at`)
    VALUES (NEW.product_id, UNIX_TIMESTAMP());

CREATE TRIGGER `neklo_monitor_inventory_update`
AFTER UPDATE ON `$citbl`
FOR EACH ROW
    INSERT INTO `$cltbl` (`product_id`, `created_at`)
    VALUES (NEW.product_id, UNIX_TIMESTAMP());

CREATE TRIGGER `neklo_monitor_inventory_delete`
AFTER DELETE ON `$citbl`
FOR EACH ROW
    INSERT INTO `$cltbl` (`product_id`, `created_at`)
    VALUES (OLD.product_id, UNIX_TIMESTAMP());

");

$installer->endSetup();