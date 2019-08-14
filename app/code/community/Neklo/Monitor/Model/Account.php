<?php

class Neklo_Monitor_Model_Account extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        $this->_init('neklo_monitor/account');
    }

    public function loadByPhoneHash($phoneHash)
    {
        return $this->load($phoneHash, 'phone_hash');
    }
}