<?php

class Neklo_Monitor_Model_Cron
{
    public function sendServerInfo(Mage_Cron_Model_Schedule $schedule)
    {
        Mage::getModel('neklo_monitor/cron_statistic_server')->run($schedule);
    }

    public function sendStoreInfo(Mage_Cron_Model_Schedule $schedule)
    {
        Mage::getModel('neklo_monitor/cron_statistic_store')->run($schedule);
    }

    public function collectVarReports(Mage_Cron_Model_Schedule $schedule)
    {
        Mage::getModel('neklo_monitor/cron_var')->collectReports($schedule);
    }

    public function convertInventoryChangelogToQueue(Mage_Cron_Model_Schedule $schedule)
    {
        Mage::getModel('neklo_monitor/cron_queue')->convertInventoryChangelogToQueue($schedule);
    }

    public function aggregateSalesReportOrderData(Mage_Cron_Model_Schedule $schedule)
    {
        Mage::getModel('neklo_monitor/cron_queue')->aggregateSalesReportOrderData($schedule);
    }

    public function sendQueue(Mage_Cron_Model_Schedule $schedule)
    {
        Mage::getModel('neklo_monitor/cron_queue')->sendQueue($schedule);
    }
}