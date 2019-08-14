<?php

class Neklo_Monitor_Model_Gateway_Connector
{
    protected $_client = null;

    /**
     * @return null|Varien_Http_Client
     * @throws Zend_Http_Client_Exception
     */
    public function getClient()
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
            ;
        }
        return $this->_client;
    }

    public function sendInfo($type, $info, $action = 'info')
    {
        if (is_null($type)) {
            // multiple types, $info is an assoc array
            $requestData = $info;
        } else {
            $requestData = array(
                $type => $info,
            );
        }

        $requestData['SID'] = $this->_getConfig()->getGatewaySid();

        $client = $this->getClient();

        $url = $this->_getUri($action);
        $client->setUri($url);
        $client->setRawData(Mage::helper('core')->jsonEncode($requestData));

        $result = $client->request();

        if (!$result->isSuccessful()) {
            throw new Exception(Mage::helper('core')->__('Error sending request to %s: %s', $url, $result->getMessage()));
        }

        return Mage::helper('core')->jsonDecode($this->_getBody($result));
    }

    protected function _getUri($action)
    {
        return $this->_getConfig()->getGatewayServerUri() . 'server/' . $action;
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
