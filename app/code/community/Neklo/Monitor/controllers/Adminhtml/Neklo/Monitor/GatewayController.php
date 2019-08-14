<?php

class Neklo_Monitor_Adminhtml_Neklo_Monitor_GatewayController extends Neklo_Monitor_Controller_Adminhtml_Abstract
{
    public function connectAction()
    {
        if (!$this->_getConfig()->isConnected()) {
            try {
                $result = $this->_getConnector()->connect();
            } catch (Exception $e) {
            }
            $this->getResponse()->setBody(Zend_Json::encode($result));
        }
    }

    public function disconnectAction()
    {
        if ($this->_getConfig()->isConnected()) {
            try {
                $result = $this->_getConnector()->disconnect();

                $accountCollection = Mage::getResourceModel('neklo_monitor/account_collection');
                /* @var $account Neklo_Monitor_Model_Account */
                foreach ($accountCollection as $account) {
                    $account->isDeleted(true);
                }
                $accountCollection->save();
            } catch (Exception $e) {
            }
            $this->getResponse()->setBody(Zend_Json::encode($result));
        }
    }
}