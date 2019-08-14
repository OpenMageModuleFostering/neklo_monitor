<?php

class Neklo_Monitor_Model_Minfo
{
    protected $_info = array();

    protected $_config = array(
        'magento' => array(
            'var_log'         => true,
            'var_report'      => true,
            'customer_online' => true,
            'products_outofstock' => true,
        ),
    );

    public function getInfo()
    {
        return $this->_info;
    }

    public function scan()
    {
        /** @var Neklo_Monitor_Model_Minfo_Parser $parser */
        $parser = Mage::getModel('neklo_monitor/minfo_parser');

        $timestamp = time();
        $fields = array(
            'var_log'         => array(
                'show'    => !empty($this->_config['magento']['var_log']),
                'default' => null,
                'method'  => 'getVarLog',
            ),
            'var_report'      => array(
                'show'    => !empty($this->_config['magento']['var_report']),
                'default' => null,
                'method'  => 'getVarReport',
            ),
            'customer_online' => array(
                'show'    => !empty($this->_config['magento']['customer_online']),
                'default' => null,
                'method'  => 'getCustomerOnline',
            ),
            'products_outofstock' => array(
                'show'    => !empty($this->_config['magento']['products_outofstock']),
                'default' => null,
                'method'  => 'getProductsOutofstock',
            ),
        );

        foreach ($fields as $key => $data) {
            $this->_info[$key] = $data['default'];

            if (!$data['show']) {
                continue;
            }

            try {
                $methodName = $data['method'];
                if (method_exists($parser, $methodName)) {
                    $this->_info[$key] = $parser->$methodName();
                }
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
        $this->_info['server_created_at'] = $timestamp;
        return $this;
    }
}
