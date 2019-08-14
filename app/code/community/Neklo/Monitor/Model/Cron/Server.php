<?php

class Neklo_Monitor_Model_Cron_Server extends Neklo_Monitor_Model_Cron_Abstract
{
    protected $_name = 'neklo_monitor_cron_server_lock_id';

    protected function _passData(Mage_Cron_Model_Schedule $schedule)
    {
        $serverData = $this->_collectServerData($schedule);
        try {
            $gatewayConfig = $this->_getConnector()->sendInfo('server', $serverData);
            $this->_getConfig()->updateGatewayConfig($gatewayConfig);
        } catch (Exception $e) {
//            Mage::logException($e);
            $msg = $schedule->getMessages();
            if ($msg) {
                $msg .= "\n";
            }
            $msg .= $e->getMessage();
            $schedule->setMessages($msg);
        }
    }

    protected function _collectServerData(Mage_Cron_Model_Schedule $schedule)
    {
        $info = null;
        try {
            Neklo_Monitor_Autoload::register();
            /** @var Neklo_Monitor_Model_Linfo $linfo */
            $linfo = Mage::getModel('neklo_monitor/linfo');
            $linfo->scan();
            $info = $linfo->getInfo();
        } catch (Exception $e) {
            Mage::logException($e);
            $msg = $schedule->getMessages();
            if ($msg) {
                $msg .= "\n";
            }
            $msg .= $e->getMessage();
            $schedule->setMessages($msg);
        }
        return $info;
    }

}
