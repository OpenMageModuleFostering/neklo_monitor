<?php

class Neklo_Monitor_Model_Resource_Log extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init('neklo_monitor/log', 'log_id');
    }
}