<?php

class Neklo_Monitor_Model_System_Config_Backend_Token extends Mage_Core_Model_Config_Data
{
    public function getValue()
    {
        return $this->_getConfig()->getToken();
    }

    /**
     * @return Neklo_Monitor_Helper_Config
     */
    protected function _getConfig()
    {
        return Mage::helper('neklo_monitor/config');
    }
}
