<?php

class Neklo_Monitor_Helper_Request
{
    protected $_postData = null;

    protected $_validateParamMap = array(
        'token'     => 'isValidToken',
        'sid'       => 'isValidSid',
        'device_id' => 'isValidDeviceId',
        'plan'      => 'isValidPlan',
        'store'     => 'isValidStore',
        'hash'      => 'isValidHash',
        'from'      => 'isValidTimestamp',
        'to'        => 'isValidTimestamp',
        'group'     => 'isValidGroupByPeriod',
        'status'    => 'isValidOrderStatus',
    );

    protected $_requestParamMap = array(
        'common//common' => array(
            'sid',
            'device_id',
            'plan',
        ),

        'auth/ping' => array(),
        'auth/index' => array( // common//common + token
            'sid',
            'device_id',
            'plan',
            'token',
        ),

        'state_indexer/list' => 'common//common',

        'state_cache/list' => 'common//common',

        'var_report/list' => 'common//common',
        'var_report/view' => array( // common//common + hash
            'sid',
            'device_id',
            'plan',
            'hash',
        ),

        'var_log/list' => 'common//common',
        'var_log/view' => array( // common//common + hash
            'sid',
            'device_id',
            'plan',
            'hash',
        ),

        'info/storeviewlist'    => 'common//common',
        'info/attrsetlist'      => 'common//common',
        'info/total' => array( // common//common + store + from
            'sid',
            'device_id',
            'plan',
            'store',
            'from',
        ),

        'dashboard//common' => array( // common//common + store
            'sid',
            'device_id',
            'plan',
            'store',
        ),
        'dashboard/total'        => 'dashboard//common',
        'dashboard/bestseller'   => 'dashboard//common',
        'dashboard/mostviewed'   => 'dashboard//common',
        'dashboard/newcustomers' => 'dashboard//common',
        'dashboard/topcustomers' => 'dashboard//common',
        'dashboard/lastorders'   => 'dashboard//common',
        'dashboard/lastsearches' => 'dashboard//common',
        'dashboard/topsearches'  => 'dashboard//common',
        'dashboard/chart'        => 'dashboard//common',

        'customer/list'          => 'dashboard//common',
        'customer/online'        => 'dashboard//common',

        'config/alertsave'       => 'dashboard//common',
        'product/outofstock'     => 'dashboard//common',
        'order/list'             => 'dashboard//common',

        'report_sales//common' => array(
            'sid',
            'device_id',
            'plan',
            'store',
            'from',
            'to',
            'group',
            'status',
        ),
        'report_sales/order'    => 'report_sales//common',
        'report_sales/tax'      => 'report_sales//common',
        'report_sales/invoiced' => 'report_sales//common',
        'report_sales/shipping' => 'report_sales//common',
        'report_sales/refunded' => 'report_sales//common',
        'report_sales/coupons'  => 'report_sales//common',
    );

    public function isValidRequest($route)
    {
        if (!array_key_exists($route, $this->_requestParamMap)) {
            // check if 404 route requested - prevent infinite redirects (controller reached 100 redirects exception)
            if (strpos($route, '/')) {
                list($_contrl, $_action) = explode('/', $route);
                if (strtolower($_action) == 'noroute') {
                    return true;
                }
            }
            return false;
        }
        if (!is_array($this->_requestParamMap[$route])) {
            // get parent action
            $route = $this->_requestParamMap[$route];
            if (!array_key_exists($route, $this->_requestParamMap)) {
                return false;
            }
        }
        $requestParamList = $this->_getPostData();
        // Validate all params
        foreach ($this->_requestParamMap[$route] as $param) {
            if (!array_key_exists($param, $requestParamList)) {
                return false;
            }
            if (array_key_exists($param, $this->_validateParamMap)) {
                $validateMethod = $this->_validateParamMap[$param];
                if (method_exists($this->getValidator(), $validateMethod) && !$this->getValidator()->$validateMethod($requestParamList[$param])) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * @return Neklo_Monitor_Helper_Request_Validator
     */
    public function getValidator()
    {
        return Mage::helper('neklo_monitor/request_validator');
    }

    public function getParam($keyName, $default)
    {
        $postData = $this->_getPostData();
        if (array_key_exists($keyName, $postData) && $postData[$keyName]) {
            return $postData[$keyName];
        }
        return $default;
    }

    /**
     * @return array
     */
    protected function _getPostData()
    {
        if ($this->_postData === null) {

            $this->_postData = array();
            $input = file_get_contents('php://input');
            if ($input
                && substr_count('{', $input) == substr_count('}', $input)
                && substr_count('[', $input) == substr_count(']', $input)) {
                try {
                    $this->_postData = Mage::helper('core')->jsonDecode($input);
                } catch (Exception $e) {

                }
            }
        }

        return $this->_postData;
    }
}
