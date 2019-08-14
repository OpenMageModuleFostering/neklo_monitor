<?php

abstract class Neklo_Monitor_Model_Cron_Abstract
{
    protected $_name = '';

    public function run(Mage_Cron_Model_Schedule $schedule)
    {
        if (!$this->_getConfig()->isEnabled()) {
            $schedule->setMessages('Disabled');
            return;
        }

        if (!$this->_getConfig()->isConnected()) {
            $schedule->setMessages('Not connected');
            return;
        }

        if ($this->_isLocked()) {
            $schedule->setMessages('Locked');
            return;
        }

        $this->_lock();
        $this->_passData($schedule);

        $msg = $schedule->getMessages();
        if ($msg) {
            $msg .= "\n";
        }
        $msg .= 'Sent';
        $schedule->setMessages($msg);
    }

    abstract protected function _passData(Mage_Cron_Model_Schedule $schedule);

    protected function _isLocked()
    {
        $lockedAt = Mage::app()->loadCache($this->_name);
        if ($lockedAt && (time() - $lockedAt < $this->_getConfig()->getGatewayFrequency() * 60)) {
            return true;
        }
        return false;
    }

    protected function _lock()
    {
        Mage::app()->saveCache(time(), $this->_name, array(), $this->_getConfig()->getGatewayFrequency() * 60);
    }

    /**
     * @return Neklo_Monitor_Helper_Config
     */
    protected function _getConfig()
    {
        return Mage::helper('neklo_monitor/config');
    }

    /**
     * @return Neklo_Monitor_Model_Gateway_Connector
     */
    protected function _getConnector()
    {
        return Mage::getSingleton('neklo_monitor/gateway_connector');
    }
}
