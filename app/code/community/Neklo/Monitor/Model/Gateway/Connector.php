<?php

class Neklo_Monitor_Model_Gateway_Connector
{
    protected $_client = null;

    public function connect()
    {
        $requestData = array(
            'token'             => $this->_getConfig()->getToken(),
            'url'               => Mage::getBaseUrl(),
            'platform'          => $this->_getConfig()->getPlatform(),
            'platform_version'  => Mage::getVersion(),
            'connector_version' => $this->_getConfig()->getModuleVersion(),
        );
        return $this->_request('store', 'connect', $requestData);
    }

    public function disconnect()
    {
        $result = $this->_request('store', 'disconnect');
        $this->_getConfig()->disconnect();
        return $result;
    }

    public function addAccount(array $accountData)
    {
        return $this->_request('account', 'add', $accountData);
    }

    public function removeAccount($phoneHash)
    {
        $requestData = array(
            'phone_hash' => $phoneHash,
        );
        return $this->_request('account', 'remove', $requestData);
    }

    public function sendInfo($type, $info, $action = 'info')
    {
        $requestData = array(
            $type => $info,
        );
        return $this->_request('server', $action, $requestData);
    }

    // TODO: check base64
    public function sendAlert($info)
    {
        $requestData = $info;
        return $this->_request('server', 'alert', $requestData);
    }

    protected function _request($controller, $action, array $data = array())
    {
        $uri = $this->_getUri($controller, $action);

        $data['SID'] = $this->_getConfig()->getGatewaySid();

        $client = $this->_getClient();
        $client->setUri($uri);
        $client->setRawData(Mage::helper('core')->jsonEncode($data));

        $result = $client->request();

        if (!$result->isSuccessful()) {
            throw new Exception(
                Mage::helper('core')->__('Error sending request to %s: %s', $uri, $result->getMessage())
            );
        }

        // TODO: update frequency config

        // TODO: improve gateway errors
//        if (!$result->isSuccessful()) {
//            try {
//                $errorResult = Mage::helper('core')->jsonDecode($this->_getBody($result));
//                $message = Mage::helper('core')->__('Gateway: %s', $errorResult['message']);
//            } catch (Exception $e) {
//                $message = $result->getMessage();
//            }
//            throw new Exception($message);
//        }

        return Mage::helper('core')->jsonDecode($this->_getBody($result));
    }

    protected function _getUri($controller, $action)
    {
        return $this->_getConfig()->getGatewayServerUri() . $controller . '/' . $action;
    }

    /**
     * @return null|Varien_Http_Client
     * @throws Zend_Http_Client_Exception
     */
    protected function _getClient()
    {
        if ($this->_client === null) {
            $this->_client = new Varien_Http_Client();
            $this->_client
                ->setMethod(Zend_Http_Client::POST)
                ->setConfig(
                    array(
                        'maxredirects' => 0,
                        'timeout'      => 30,
                        'verifypeer'   => 0,
                    )
                )
                ->setHeaders(
                    Neklo_Monitor_Helper_Header::GATEWAY_API_VERSION_HEADER,
                    $this->_getConfig()->getGatewayApiVersion()
                )
            ;
        }
        return $this->_client;
    }

    /**
     * @return Neklo_Monitor_Helper_Config
     */
    protected function _getConfig()
    {
        return Mage::helper('neklo_monitor/config');
    }

    /**
     * similar to Zend_Http_Response::getBody()
     */
    protected function _getBody(Zend_Http_Response $response)
    {
        $body = $response->getRawBody();

        // 'transfer-encoding' header is 'chunked', but the body does not seem to be chunked, hmm
        // just silent catch for such cases
        try {
            // Decode the body if it was transfer-encoded
            if (strtolower($response->getHeader('transfer-encoding')) == 'chunked') {
                // Handle chunked body
                $body = Zend_Http_Response::decodeChunkedBody($body);
            }
        } catch (Zend_Http_Exception $e) {
            if (false === strpos($e->getMessage(), 'Error parsing body')) {
                throw $e;
            }
        }

        // Decode any content-encoding (gzip or deflate) if needed
        switch (strtolower($response->getHeader('content-encoding'))) {

            // Handle gzip encoding
            case 'gzip':
                $body = Zend_Http_Response::decodeGzip($body);
                break;

            // Handle deflate encoding
            case 'deflate':
                $body = Zend_Http_Response::decodeDeflate($body);
                break;

            default:
                break;
        }

        return $body;
    }
}
