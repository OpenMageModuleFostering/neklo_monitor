<?php

class Neklo_Monitor_State_CacheController extends Neklo_Monitor_Controller_Abstract
{
    public function listAction()
    {
        // get cachers list and their statuses

        $cachers = array();
        $invalidatedTypes = Mage::app()->getCacheInstance()->getInvalidatedTypes();
        foreach (Mage::app()->getCacheInstance()->getTypes() as $type => $data) {
            if (isset($invalidatedTypes[$data->getId()])) {
                $status = 2; // $this->__('Invalidated');
            } else {
                if ($data->getStatus()) {
                    $status = 1; // $this->__('Enabled');
                } else {
                    $status = 0; // $this->__('Disabled');
                }
            }

            $cachers[] = array(
                'id'   => $data->getId(),
                'label'  => $data->getCacheType(),
                'status' => $status,
            );
        }

        $result = array(
            'result' => $cachers,
        );

        $this->_jsonResult($result);
    }

    public function refreshAction()
    {
        // TODO
    }

    public function flushallAction()
    {
        // TODO
    }

}