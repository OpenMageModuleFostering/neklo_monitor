<?php

class Neklo_Monitor_AuthController extends Neklo_Monitor_Controller_Abstract
{
    public function preDispatch()
    {
        // allow disconnected requests to any auth-action
        // either indexAction or norouteAction()
        // @see parent::preDispatch
        $this->_allowConnectedOnly = false;

        return parent::preDispatch();
    }

    public function indexAction()
    {
        if (!$this->_getConfigHelper()->isConnected()) {
            $sid = $this->_getRequestHelper()->getParam('sid', null);
            if ($sid !== null) {
                $this->_getConfigHelper()->connect($sid);
            }
        }

        // Return store icon and store name
        $result = array(
            'name' => Mage::getStoreConfig('design/head/default_title'),
            'icon' => Mage::getDesign()->getSkinUrl('favicon.ico'),
        );

        $this->_jsonResult($result);
    }

    public function pingAction()
    {
        $this->_jsonResult(array('ping' => 'Ok'));
    }
}
