<?php

class Neklo_Monitor_Helper_Config extends Mage_Core_Helper_Data
{
    const GENERAL_IS_ENABLED = 'neklo_monitor/general/is_enabled';

    const SECURITY_TOKEN = 'neklo_monitor/gateway/token';
    const SECURITY_TOKEN_GENERATED_AT = 'neklo_monitor/gateway/token_generated_at';
    const SECURITY_TOKEN_INTERVAL = 600;

    const GATEWAY_SERVER_TYPE = 'neklo_monitor/gateway/server_type';
    const GATEWAY_SID = 'neklo_monitor/gateway/sid';
    const GATEWAY_PLAN = 'neklo_monitor/gateway/plan_type';
    const GATEWAY_FREQUENCY = 'neklo_monitor/gateway/plan_frequency';
    const GATEWAY_LAST_UPDATE = 'neklo_monitor/gateway/last_update';

    protected $_gatewayConfig = array(
        'type'      => self::GATEWAY_PLAN,
        'frequency' => self::GATEWAY_FREQUENCY,
    );

    public function getModuleVersion()
    {
        return (string) Mage::getConfig()->getNode('modules/Neklo_Monitor/version');
    }

    public function isEnabled()
    {
        return Mage::getStoreConfigFlag(self::GENERAL_IS_ENABLED);
    }

    public function getToken()
    {
        if ($this->_isNeedUpdateToken()) {
            return $this->_updateToken();
        }
        return Mage::getStoreConfig(self::SECURITY_TOKEN);
    }

    protected function _isNeedUpdateToken()
    {
        return (time() - $this->getTokenCreatedAt() > self::SECURITY_TOKEN_INTERVAL);
    }

    protected function _updateToken()
    {
        $hash = mcrypt_create_iv(16, MCRYPT_DEV_URANDOM) . Mage::getStoreConfig(self::SECURITY_TOKEN);
        $hash = Mage::helper('core')->encrypt($hash);
        $hash = preg_replace("/[^A-Za-z]/", '', $hash);
        $token = strtoupper(substr($hash, 0, 5));
        $this->_saveConfig(self::SECURITY_TOKEN, $token);
        $this->_updateTokenCreatedAt();

        // reinit configuration cache
        Mage::getConfig()->reinit();

        return $token;
    }

    protected function _updateTokenCreatedAt($time = null)
    {
        if (is_null($time)) {
            $time = time();
        }
        $this->_saveConfig(self::SECURITY_TOKEN_GENERATED_AT, (int)$time);
    }

    public function getTokenCreatedAt()
    {
        return (int)Mage::getStoreConfig(self::SECURITY_TOKEN_GENERATED_AT);
    }

    public function getGatewayServerType()
    {
        return Mage::getStoreConfig(self::GATEWAY_SERVER_TYPE);
    }

    public function getGatewayServerUri()
    {
        $serverType = $this->getGatewayServerType();
        return Mage::getModel('neklo_monitor/system_config_source_server_type')->getServerUri($serverType);
    }

    public function getGatewaySid()
    {
        $serverType = $this->getGatewayServerType();
        return Mage::helper('core')->decrypt(Mage::getStoreConfig(self::GATEWAY_SID . '_' . $serverType));
    }

    public function isConnected($serverType = null)
    {
        if (is_null($serverType)) {
            $serverType = $this->getGatewayServerType();
        }
        return Mage::getStoreConfigFlag(self::GATEWAY_SID . '_' . $serverType);
    }

    public function connect($sid)
    {
        $serverType = $this->getGatewayServerType();
        $encryptedSid = Mage::helper('core')->encrypt($sid);
        $this->_saveConfig(self::GATEWAY_SID . '_' . $serverType, $encryptedSid);
        $this->_updateTokenCreatedAt(0); // invalidate Token

        // reinit configuration cache
        Mage::getConfig()->reinit();
    }

    public function updateGatewayConfig($config)
    {
        $serverType = $this->getGatewayServerType();
        foreach ($this->_gatewayConfig as $field => $configPath) {
            if (!array_key_exists($field, $config) || !$config[$field]) {
                continue;
            }
            $this->_saveConfig($configPath . '_' . $serverType, $config[$field]);
        }

        $this->_updateGatewayLastUpdate();

        // reinit configuration cache
        Mage::getConfig()->reinit();
    }

    public function getGatewayPlan()
    {
        $serverType = $this->getGatewayServerType();
        return Mage::getStoreConfig(self::GATEWAY_PLAN . '_' . $serverType);
    }

    public function getGatewayFrequency()
    {
        $serverType = $this->getGatewayServerType();
        return Mage::getStoreConfig(self::GATEWAY_FREQUENCY . '_' . $serverType);
    }

    public function getGatewayLastUpdate()
    {
        $serverType = $this->getGatewayServerType();
        return Mage::getStoreConfig(self::GATEWAY_LAST_UPDATE . '_' . $serverType);
    }

    protected function _updateGatewayLastUpdate()
    {
        $serverType = $this->getGatewayServerType();
        $this->_saveConfig(self::GATEWAY_LAST_UPDATE . '_' . $serverType, time());
    }

    protected function _saveConfig($path, $value, $scope = 'default', $scopeId = 0)
    {
        $configModel = Mage::getModel('core/config');
        $configModel->saveConfig($path, $value, $scope, $scopeId);
    }

}
