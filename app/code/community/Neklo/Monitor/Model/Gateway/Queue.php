<?php

class Neklo_Monitor_Model_Gateway_Queue extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        $this->_init('neklo_monitor/gateway_queue');
    }

    public function addToQueue($type, $info)
    {
        $this
            ->setId(null)
            ->setType($type)
            ->setMessage(Mage::helper('core')->jsonEncode($info))
            ->setScheduledAt(time())
            ->save()
        ;
        return $this;
    }
}