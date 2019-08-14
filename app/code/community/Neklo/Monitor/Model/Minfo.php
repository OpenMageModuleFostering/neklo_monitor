<?php

class Neklo_Monitor_Model_Minfo
{
    protected $_info = array();

    protected $_config = array(
        'magento' => array(
            'customer_online'     => true,
            'products_outofstock' => true,
        ),
        'var'     => array(
            'log_size'          => true,
            'log_file_count'    => true,
            'log_qty'           => true,
            'log_qty_new'       => true,
            'report_size'       => true,
            'report_file_count' => true,
            'report_qty'        => true,
            'report_qty_new'    => true,
        )
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
            'log_size'    => array(
                'show'    => !empty($this->_config['var']['log_size']),
                'default' => null,
                'method'  => 'getLogSize',
            ),
            'log_file_count'    => array(
                'show'    => !empty($this->_config['var']['log_file_count']),
                'default' => null,
                'method'  => 'getLogFileCount',
            ),
            'log_qty'     => array(
                'show'    => !empty($this->_config['var']['log_qty']),
                'default' => null,
                'method'  => 'getLogQty',
            ),
            'log_qty_new' => array(
                'show'    => !empty($this->_config['var']['log_qty_new']),
                'default' => null,
                'method'  => 'getLogQtyNew',
            ),
            'report_size'      => array(
                'show'    => !empty($this->_config['var']['report_size']),
                'default' => null,
                'method'  => 'getReportSize',
            ),
            'report_file_count'      => array(
                'show'    => !empty($this->_config['var']['report_file_count']),
                'default' => null,
                'method'  => 'getReportFileCount',
            ),
            'report_qty'      => array(
                'show'    => !empty($this->_config['var']['report_qty']),
                'default' => null,
                'method'  => 'getReportQty',
            ),
            'report_qty_new'      => array(
                'show'    => !empty($this->_config['var']['report_qty_new']),
                'default' => null,
                'method'  => 'getReportQtyNew',
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
