<?php

class Neklo_Monitor_Model_System_Config_Source_Server_Type
{
    const PRODUCTION_CODE = 'production';
    const PRODUCTION_LABEL = 'Production';
    const PRODUCTION_URL = 'https://magento1-m1stats.neklodev.com/';

    const SANDBOX_CODE = 'sandbox';
    const SANDBOX_LABEL = 'Sandbox';
    const SANDBOX_URL = 'https://magento1-m1stats.neklodev.com/';

    public function toOptionArray()
    {
        $helper = Mage::helper('neklo_monitor');
        return array(
            array(
                'value' => self::PRODUCTION_CODE,
                'label' => $helper->__(self::PRODUCTION_LABEL)
            ),
            array(
                'value' => self::SANDBOX_CODE,
                'label' => $helper->__(self::SANDBOX_LABEL)
            ),
        );
    }

    public function getServerUri($type)
    {
        if ($type === self::PRODUCTION_CODE) {
            return self::PRODUCTION_URL;
        }
        return self::SANDBOX_URL;
    }
}
