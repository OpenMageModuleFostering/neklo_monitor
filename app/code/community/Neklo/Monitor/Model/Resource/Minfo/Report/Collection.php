<?php


class Neklo_Monitor_Model_Resource_Minfo_Report_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    protected function _construct()
    {
        $this->_init('neklo_monitor/minfo_report');
    }
}