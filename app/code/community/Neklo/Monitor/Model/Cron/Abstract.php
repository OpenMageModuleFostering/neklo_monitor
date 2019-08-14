<?php

abstract class Neklo_Monitor_Model_Cron_Abstract
{
    /**
     * @return Neklo_Monitor_Model_Gateway_Connector
     */
    protected function _getConnector()
    {
        return Mage::getSingleton('neklo_monitor/gateway_connector');
    }

    /**
     * @return Neklo_Monitor_Helper_Config
     */
    protected function _getConfig()
    {
        return Mage::helper('neklo_monitor/config');
    }
}