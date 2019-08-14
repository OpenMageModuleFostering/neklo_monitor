<?php
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();
$installer->run("
    CREATE TABLE `{$installer->getTable('neklo_monitor/account')}` (
        `entity_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `phone_hash` varchar(32) NOT NULL,
        `phone_mask` varchar(255) NOT NULL,
        `firstname` varchar(255) DEFAULT NULL,
        `lastname` varchar(255) DEFAULT NULL,
        `email` varchar(255) DEFAULT NULL,
        PRIMARY KEY (`entity_id`),
        UNIQUE KEY `phone_hash` (`phone_hash`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
    
    ALTER TABLE `{$installer->getTable('neklo_monitor/report')}`
        ADD COLUMN `qty_new` INT(11) UNSIGNED NOT NULL AFTER `qty`;
        
    ALTER TABLE `{$installer->getTable('neklo_monitor/log')}`
        ADD COLUMN `qty_new` INT(11) UNSIGNED NOT NULL AFTER `qty`,
        DROP COLUMN `type`,
        DROP COLUMN `times`;
");
$installer->endSetup();