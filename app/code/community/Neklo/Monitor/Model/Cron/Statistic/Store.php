<?php

class Neklo_Monitor_Model_Cron_Statistic_Store extends Neklo_Monitor_Model_Cron_Statistic
{
    protected $_name = 'neklo_monitor_cron_store_lock_id';

    protected function _passData(Mage_Cron_Model_Schedule $schedule)
    {
        $storeData = $this->_collectStoreData($schedule);
        try {
            $gatewayConfig = $this->_getConnector()->sendInfo('store', $storeData);
            $this->_getConfig()->updateGatewayConfig($gatewayConfig);
        } catch (Exception $e) {
            Mage::logException($e);
            $msg = $schedule->getMessages();
            if ($msg) {
                $msg .= "\n";
            }
            $msg .= $e->getMessage();
            $schedule->setMessages($msg);
        }
    }

    protected function _collectStoreData(Mage_Cron_Model_Schedule $schedule)
    {
        $info = null;
        try {
            /* @var $minfo Neklo_Monitor_Model_Minfo */
            $minfo = Mage::getModel('neklo_monitor/minfo');
            $minfo->scan();
            $info = $minfo->getInfo();
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
