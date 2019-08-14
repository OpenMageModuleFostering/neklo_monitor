<?php

class Neklo_Monitor_Model_Cron_Var extends Neklo_Monitor_Model_Cron_Abstract
{
    /**
     * Collect var/report to DB
     */
    public function collectReports()
    {
        if (!$this->_getConfig()->isEnabled()) {
            return;
        }

        $reportList = Mage::helper('neklo_monitor/var_report')->collectReports();
        Mage::getModel('neklo_monitor/report')->saveReports($reportList);
    }
}