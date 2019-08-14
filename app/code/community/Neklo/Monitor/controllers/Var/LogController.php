<?php

class Neklo_Monitor_Var_LogController extends Neklo_Monitor_Controller_Abstract
{
    public function listAction()
    {
        /* @var $collection Neklo_Monitor_Model_Resource_Minfo_Log_Collection */
        $collection = Mage::getResourceModel('neklo_monitor/minfo_log_collection');

        // for pages lists - load next page rows despite newly inserted rows
        $queryTimestamp = (int) $this->_getRequestHelper()->getParam('query_timestamp', 0);
        if ($queryTimestamp > 0) {
            $collection->addFieldToFilter('last_time', array('lt' => $queryTimestamp));
        }

        $offset = $this->_getRequestHelper()->getParam('offset', 0);
        $page = ceil($offset / self::PAGE_SIZE) + 1;
        $collection->setPageSize(self::PAGE_SIZE);
        $collection->setCurPage($page);

        $collection->setOrder('last_time');

        $list = array('result' => array());
        foreach ($collection as $log) {
            /** @var Neklo_Monitor_Model_Minfo_Log $log */
            $list['result'][] = array(
                'type'       => $log->getData('type'),
                'hash'       => $log->getData('hash'),
                'message'    => $log->getData('message'),
                'qty'        => (int)$log->getData('qty'),
                'last_time'  => (int)$log->getData('last_time'),
                'first_time' => (int)$log->getData('first_time'),
            );
        }
//        $list['sql1'] = $collection->getSelectSql(true);

        // get new entities count

        if ($queryTimestamp > 0) {
            /* @var $collection Neklo_Monitor_Model_Resource_Minfo_Log_Collection */
            $collection = Mage::getResourceModel('neklo_monitor/minfo_log_collection');
            $collection->addFieldToFilter('last_time', array('gteq' => $queryTimestamp));
            $list['new_entities_count'] = $collection->getSize();
//            $list['sql2'] = $collection->getSelectCountSql()->__toString();
        }

        $this->_jsonResult($list);
    }

    public function viewAction()
    {
        $hash = $this->_getRequestHelper()->getParam('hash', '');

        /* @var $log Neklo_Monitor_Model_Minfo_Log */
        $log = Mage::getModel('neklo_monitor/minfo_log');
        $log->load($hash, 'hash');

        // for pages lists - load next page rows despite newly inserted rows
        $filter = array();
        $queryTimestamp = (int) $this->_getRequestHelper()->getParam('query_timestamp', 0);
        if ($queryTimestamp > 0) {
            $filter = array('lt' => $queryTimestamp);
        }

        $offset = $this->_getRequestHelper()->getParam('offset', 0);

        $collection = $log->getTimesCollection($offset, self::PAGE_SIZE, $filter);

        $list = array('result' => array());
        Mage::log($collection->getItems());
        foreach ($collection as $_logTime) {
            /** @var Varien_Object $_logTime */
            $list['result'][] = (int)$_logTime->getData();
        }

        // get new entities count

        if ($queryTimestamp > 0) {
            $collection = $log->getTimesCollection(0, null, array('gteq' => $queryTimestamp));
            $list['new_entities_count'] = $collection->getSize();
        }

        $this->_jsonResult($list);
    }

}