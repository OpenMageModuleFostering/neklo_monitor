<?php

class Neklo_Monitor_Helper_Request_Validator
{
    public function isValidToken($token)
    {
        if (!$token) {
            return false;
        }
        if ($this->_getConfig()->getToken() !== $token) {
            return false;
        }
        return true;
    }

    public function isValidSid($sid)
    {
        if (strlen($sid) !== 32) {
            return false;
        }
        if ($this->_getConfig()->getGatewaySid() && $this->_getConfig()->getGatewaySid() !== $sid) {
            return false;
        }
        return true;
    }

    public function isValidHash($hash)
    {
        if (strlen($hash) !== 32) {
            return false;
        }
        return true;
    }

    public function isValidDeviceId($deviceId)
    {
        if (!$deviceId) {
            return false;
        }
        // TODO: add expression for device id
        return true;
    }

    public function isValidPlan($plan)
    {
        if (!is_array($plan)) {
            return false;
        }
        if (!array_key_exists('type', $plan) || !$plan['type']) {
            return false;
        }
        if (!array_key_exists('frequency', $plan) || !$plan['frequency']) {
            return false;
        }
        return true;
    }

    public function isValidStore($storeId)
    {
        $storeId = (int)$storeId;
        if ($storeId) {
            $store = Mage::app()->getStore($storeId);
            if (!$store->getId() || $storeId != $store->getId()) {
                return false;
            }
        }
        return true;
    }

    public function isValidTimestamp($time)
    {
        if (is_numeric($time)) {
            return true;
        }
        return false;
    }

    public function isValidGroupByPeriod($period)
    {
        if (!in_array($period, array(
            'day', 'month', 'year'
        ))) {
            return false;
        }
        return true;
    }

    public function isValidOrderStatus($status)
    {
        // parameter is optional
        if (!$status) {
            return true;
        }
        // but we validate its value if sent
        if (!in_array($status, array_keys(Mage::getSingleton('sales/order_config')->getStatuses()))) {
            return false;
        }
        return true;
    }

    /**
     * @return Neklo_Monitor_Helper_Config
     */
    protected function _getConfig()
    {
        return Mage::helper('neklo_monitor/config');
    }
}
