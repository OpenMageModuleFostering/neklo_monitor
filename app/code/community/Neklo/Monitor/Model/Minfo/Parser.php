<?php

class Neklo_Monitor_Model_Minfo_Parser
{
    const VAR_REPORT = 'report';
    const VAR_LOG    = 'log';

    protected $_directoryStatList = array();

    protected $_logStats = null;
    protected $_reportStats = null;

    public function getLogSize()
    {
        $stats = $this->_getDirectoryStats(self::VAR_LOG);
        return $stats->getSize();
    }

    public function getLogFileCount()
    {
        $stats = $this->_getDirectoryStats(self::VAR_LOG);
        return $stats->getCount();
    }

    public function getLogQty()
    {
        $stats = $this->_getLogStats();
        return $stats->getQty();
    }

    public function getLogQtyNew()
    {
        $stats = $this->_getLogStats();
        return $stats->getQtyNew();
    }

    public function getReportSize()
    {
        $stats = $this->_getDirectoryStats(self::VAR_REPORT);
        return $stats->getSize();
    }

    public function getReportFileCount()
    {
        $stats = $this->_getDirectoryStats(self::VAR_REPORT);
        return $stats->getCount();
    }

    public function getReportQty()
    {
        $stats = $this->_getReportStats();
        return $stats->getQty();
    }

    public function getReportQtyNew()
    {
        $stats = $this->_getReportStats();
        return $stats->getQtyNew();
    }

    public function getCustomerOnline()
    {
        /* @var Mage_Log_Model_Visitor_Online $logModel */
        $logModel = Mage::getModel('log/visitor_online');
        $logModel->prepare();
        /* @var $collection Mage_Log_Model_Mysql4_Visitor_Online_Collection */
        $collection = $logModel->getCollection();
        return $collection->getSize();
    }

    public function getProductsOutofstock()
    {
        /** @var Neklo_Monitor_Helper_Product $productHelper */
        $productHelper = Mage::helper('neklo_monitor/product');
        $collection = $productHelper->getProductsOutofstockCollection();
        return $collection->getSize();
    }

    // TODO: move to var.php helper
    protected function _getDirectoryStats($directory)
    {
        if (!array_key_exists($directory, $this->_directoryStatList)) {
            $directoryPath = Mage::getBaseDir('var') . DS . $directory;
            if (!is_dir($directoryPath) || !is_readable($directoryPath)) {
                return new Varien_Object();
            }

            $size = 0;
            $count = 0;
            $directoryIterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directoryPath)
            );
            foreach ($directoryIterator as $file) {
                /* @var $file SplFileInfo */
                if (!$file->isFile()) {
                    continue;
                }
                $size += $file->getSize();
                $count++;
            }

            $stats = new Varien_Object(array(
                'size'  => (int) $size,
                'count' => (int) $count,
            ));

            $this->_directoryStatList[$directory] = $stats;
        }
        return $this->_directoryStatList[$directory];
    }

    protected function _getLogStats()
    {
        if ($this->_logStats === null) {
            /* @var Neklo_Monitor_Model_Resource_Log_Collection $collection */
            $collection = Mage::getResourceModel('neklo_monitor/log_collection');
            $this->_logStats = new Varien_Object(
                $collection->getTotal(true)
            );
        }
        return $this->_logStats;
    }

    protected function _getReportStats()
    {
        if ($this->_reportStats === null) {
            /* @var Neklo_Monitor_Model_Resource_Report_Collection $collection */
            $collection = Mage::getResourceModel('neklo_monitor/report_collection');
            $this->_reportStats = new Varien_Object(
                $collection->getTotal(true)
            );
        }
        return $this->_reportStats;
    }
}