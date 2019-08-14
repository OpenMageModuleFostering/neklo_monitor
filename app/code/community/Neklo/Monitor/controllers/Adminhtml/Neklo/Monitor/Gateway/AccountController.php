<?php

class Neklo_Monitor_Adminhtml_Neklo_Monitor_Gateway_AccountController extends Neklo_Monitor_Controller_Adminhtml_Abstract
{
    public function addAction()
    {
        $result = array(
            'success'  => true,
            'messages' => array(),
        );

        if (!$this->_getConfig()->isConnected()) {
            $result['success'] = false;
            $result['messages'][] = Mage::helper('neklo_monitor')->__('Gateway is not connected.');
            $this->getResponse()->setBody(Zend_Json::encode($result));
            return;
        }

        $accountKeyList = array(
            'phone_mask',
            'firstname',
            'lastname',
            'email',
        );

        $accountData = $this->getRequest()->getParams();

        foreach ($accountData as $key => $value) {
            if (!in_array($key, $accountKeyList)) {
                unset($accountData[$key]);
            }
        }

        if (!is_array($accountData) || !count($accountData)) {
            $result['success'] = false;
            $result['messages'][] = Mage::helper('neklo_monitor')->__('Account data is not passed.');
            $this->getResponse()->setBody(Zend_Json::encode($result));
            return;
        }

        $accountData['phone'] = $accountData['phone_mask'];

        $gatewayResult = $this->_getConnector()->addAccount($accountData);

        if (
            !is_array($gatewayResult) || !array_key_exists('success', $gatewayResult) || !$gatewayResult['success']
            || !array_key_exists('phone_hash', $gatewayResult) || !$gatewayResult['phone_hash']
        ) {
            $result['success'] = false;
            $result['messages'][] = Mage::helper('neklo_monitor')->__('Account is not created at gateway.');
            $this->getResponse()->setBody(Zend_Json::encode($result));
            return;
        }

        $accountModel = Mage::getModel('neklo_monitor/account')->loadByPhoneHash($result['phone_hash']);
        if (!$accountModel->getId()) {
            $accountData['phone_mask'] = $this->_phoneMask($accountData['phone_mask']);
            $accountData['phone_hash'] = $gatewayResult['phone_hash'];
            $accountModel->addData($accountData);
            $accountModel->save();
        }

        $result['account'] = array(
            'id'         => $accountModel->getId(),
            'phone_mask' => $accountModel->getPhoneMask(),
        );

        $result['messages'][] = Mage::helper('neklo_monitor')->__('Account is created.');
        $this->getResponse()->setBody(Zend_Json::encode($result));
    }

    public function removeAction()
    {
        $result = array(
            'success'  => true,
            'messages' => array(),
        );

        if (!$this->_getConfig()->isConnected()) {
            $result['success'] = false;
            $result['messages'][] = Mage::helper('neklo_monitor')->__('Gateway is not connected.');
            $this->getResponse()->setBody(Zend_Json::encode($result));
            return;
        }

        $accountId = $this->getRequest()->getParam('entity_id', null);

        $accountModel = Mage::getModel('neklo_monitor/account')->load($accountId);
        if (!$accountModel->getId()) {
            $result['success'] = false;
            $result['messages'][] = Mage::helper('neklo_monitor')->__('Account is not exists.');
            $this->getResponse()->setBody(Zend_Json::encode($result));
            return;
        }

        try {
            $this->_getConnector()->removeAccount(
                $accountModel->getPhoneHash()
            );
        } catch (Exception $e) {
            $result['success'] = false;
            $result['messages'][] = Mage::helper('neklo_monitor')->__('Account is not exists.');
            $this->getResponse()->setBody($e->getMessage());
        }

        $accountModel->delete();

        $result['messages'][] = Mage::helper('neklo_monitor')->__('Account hsa been deleted.');
        $this->getResponse()->setBody(Zend_Json::encode($result));
    }

    protected function _phoneMask($phone)
    {
        $start = 4;
        $end = 2;

        $startPart = substr($phone, 0, $start);
        $maskPart = str_repeat('*', strlen($phone) - $start - $end);
        $endPart = substr($phone, -$end);

        return $startPart . $maskPart . $endPart;
    }
}