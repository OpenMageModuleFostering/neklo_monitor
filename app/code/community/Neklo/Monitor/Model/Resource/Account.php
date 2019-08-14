<?php

class Neklo_Monitor_Model_Resource_Account extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init('neklo_monitor/account', 'entity_id');
    }
}