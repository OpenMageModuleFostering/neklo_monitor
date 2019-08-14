<?php

class Neklo_Monitor_Controller_Abstract extends Mage_Core_Controller_Front_Action
{
    const PAGE_SIZE = 50;
    const API_VERSION_HEADER = 'X-API-Version';

    // some controllers (auth) should skip isConnected checking @see preDispatch
    protected $_allowConnectedOnly = true;

    public function preDispatch()
    {
        parent::preDispatch();

        if (!$this->_getConfigHelper()->isEnabled()) {
            $this->_forward('noRoute');
            return $this;
        }

        // similar to $this->getFullActionName()
        $action = strtolower($this->getRequest()->getRequestedControllerName().'/'.
            $this->getRequest()->getRequestedActionName());

        if (!$this->_getRequestHelper()->isValidRequest($action)) {
            $this->_forward('noRoute');
            return $this;
        }

        if ($this->_allowConnectedOnly && !$this->_getConfigHelper()->isConnected()) {
            $this->_forward('noRoute');
            return $this;
        }

        $accountPlan = $this->_getRequestHelper()->getParam('plan', null);
        if (!isset($accountPlan['type'])) {
            $accountPlan['type'] = false;
        }
        if (!isset($accountPlan['frequency'])) {
            $accountPlan['frequency'] = false;
        }
        $this->_getConfigHelper()->updateGatewayConfig(
            array(
                'type'      => $accountPlan['type'],
                'frequency' => $accountPlan['frequency'],
            )
        );

        Mage::register('neklo_monitor_request', true, true);

        return $this;
    }

    public function postDispatch()
    {
        $this->getResponse()->setHeader(self::API_VERSION_HEADER, $this->_getConfigHelper()->getModuleVersion());
        return parent::postDispatch();
    }

    protected function _jsonResult($data)
    {
        $this->getResponse()->setHeader('Content-type', 'text/json');
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($data));
    }

    /**
     * @return Neklo_Monitor_Helper_Request
     */
    protected function _getRequestHelper()
    {
        return Mage::helper('neklo_monitor/request');
    }

    /**
     * @return Neklo_Monitor_Helper_Config
     */
    protected function _getConfigHelper()
    {
        return Mage::helper('neklo_monitor/config');
    }

}