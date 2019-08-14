<?php


class Neklo_Monitor_Model_Resource_Gateway_Queue_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    protected function _construct()
    {
        $this->_init('neklo_monitor/gateway_queue');
    }
}