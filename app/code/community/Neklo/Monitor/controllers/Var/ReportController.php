<?php

class Neklo_Monitor_Var_ReportController extends Neklo_Monitor_Controller_Abstract
{
    public function listAction()
    {
        /* @var $collection Neklo_Monitor_Model_Resource_Report_Collection */
        $collection = Mage::getResourceModel('neklo_monitor/report_collection');

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
        foreach ($collection as $report) {
            /* @var Neklo_Monitor_Model_Report $report */
            $list['result'][] = array(
                'hash'       => $report->getData('hash'),
                'message'    => $report->getData('message'),
                'qty'        => (int) $report->getData('qty'),
                'last_time'  => (int) $report->getData('last_time'),
                'first_time' => (int) $report->getData('first_time'),
            );
        }
//        $list['sql1'] = $collection->getSelectSql(true);

        // get new entities count

        if ($queryTimestamp > 0) {
            /* @var $collection Neklo_Monitor_Model_Resource_Report_Collection */
            $collection = Mage::getResourceModel('neklo_monitor/report_collection');
            $collection->addFieldToFilter('last_time', array('gteq' => $queryTimestamp));
            $list['new_entities_count'] = $collection->getSize();
//            $list['sql2'] = $collection->getSelectCountSql()->__toString();
        }

        $this->_jsonResult($list);
    }

    public function viewAction()
    {
        $hash = $this->_getRequestHelper()->getParam('hash', '');

        /* @var $report Neklo_Monitor_Model_Report */
        $report = Mage::getModel('neklo_monitor/report');
        $report->load($hash, 'hash');

        // for pages lists - load next page rows despite newly inserted rows
        $filter = array();
        $queryTimestamp = (int) $this->_getRequestHelper()->getParam('query_timestamp', 0);
        if ($queryTimestamp > 0) {
            $filter = array('mtime' => array('lt' => $queryTimestamp));
        }

        $offset = $this->_getRequestHelper()->getParam('offset', 0);

        $collection = $report->getFilesCollection($offset, self::PAGE_SIZE, $filter);

        $list = array('result' => array());
        foreach ($collection as $_reportFile) {
            /** @var Varien_Object $_reportFile */
            $list['result'][] = array(
                'path' => '' . $_reportFile->getData('path'),
                'name' => '' . $_reportFile->getData('name'),
                'time' => (int) $_reportFile->getData('time'),
                'size' => (int) $_reportFile->getData('size'),
            );
        }

        // get new entities count

        if ($queryTimestamp > 0) {
            $collection = $report->getFilesCollection(0, null, array('mtime' => array('gteq' => $queryTimestamp)));
            $list['new_entities_count'] = $collection->getSize();
        }

        $this->_jsonResult($list);
    }

}