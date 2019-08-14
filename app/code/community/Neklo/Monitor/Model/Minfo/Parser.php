<?php

class Neklo_Monitor_Model_Minfo_Parser
{
    const VAR_REPORT = 'report';
    const VAR_LOG    = 'log';

    public function getVarLog()
    {
        $statFiles = $this->_getDirectoryStats(self::VAR_LOG);
        $statDb = $this->_getCollectedLogStats();
        return array(
            'size'    => $statFiles->getSize(),
            'count'   => $statFiles->getCount(),
            'details' => $statDb->getDetails(),
        );
    }

    public function getVarReport()
    {
        $statFiles = $this->_getDirectoryStats(self::VAR_REPORT);
        $statDb = $this->_getCollectedReportStats();
        return array(
            'size'    => $statFiles->getSize(),
            'count'   => $statFiles->getCount(),
            'details' => $statDb->getDetails(),
        );
    }

    protected function _getDirectoryStats($directory)
    {
        $directoryPath = Mage::getBaseDir('var') . DS . $directory;
        if (!is_dir($directoryPath) || !is_readable($directoryPath)) {
            return new Varien_Object();
        }

        $size = 0;
        $count = 0;
        $directoryIterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directoryPath));
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

        return $stats;
    }

    protected function _getCollectedLogStats()
    {
        /** @var Neklo_Monitor_Model_Resource_Minfo_Log_Collection $collection */
        $collection = Mage::getResourceModel('neklo_monitor/minfo_log_collection');
        $collection->addFieldToSelect(array('type', 'qty', 'hash'));
        $collection->getSelect(); // Init fields for select
        $collection->load();

        $gatewayReport = array();
        foreach ($collection as $hash => $data) {
            $gatewayReport[] = array(
                'type' => $data['type'],
                'hash' => $data['hash'],
                'qty'  => (int) $data['qty'],
            );
        }

        $stats = new Varien_Object(array(
            'details'  => $gatewayReport,
        ));

        return $stats;
    }

    protected function _getCollectedReportStats()
    {
        /** @var Neklo_Monitor_Model_Resource_Minfo_Report_Collection $collection */
        $collection = Mage::getResourceModel('neklo_monitor/minfo_report_collection');
        $collection->addFieldToSelect(array('qty', 'hash'));
        $collection->getSelect(); // Init fields for select
        $collection->load();

        $gatewayReport = array();
        foreach ($collection as $hash => $data) {
            $gatewayReport[] = array(
                'hash' => $data['hash'],
                'qty'  => (int) $data['qty'],
            );
        }

        $stats = new Varien_Object(array(
            'details'  => $gatewayReport,
        ));

        return $stats;
    }

    public function getCustomerOnline()
    {
        /** @var Mage_Log_Model_Visitor_Online $logModel */
        $logModel = Mage::getModel('log/visitor_online');
        $logModel->prepare();
        /* @var $collection Mage_Log_Model_Mysql4_Visitor_Online_Collection */
        $collection = $logModel->getCollection();
        return array('count' => $collection->getSize());
    }

    public function getProductsOutofstock()
    {
        $collection = $this->getProductsOutofstockCollection();
        return array('count' => $collection->getSize());
    }

    /**
     * @param null $storeId
     * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection
     */
    public function getProductsOutofstockCollection($storeId = null)
    {
        /* @var $collection Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection */
        $collection = Mage::getResourceModel('catalog/product_collection');
        if ($storeId) {
            $collection->addStoreFilter($storeId);
        }

        $collection->addAttributeToFilter(
            'status',
            array('in' => Mage::getSingleton('catalog/product_status')->getSaleableStatusIds())
        );

        // copy-pasted from CE 1.4 Layer Model
        /*
        $attributes = Mage::getSingleton('catalog/config')->getProductAttributes();
        $collection->addAttributeToSelect($attributes)
            ->addMinimalPrice()
            ->addFinalPrice()
            ->addTaxPercents()
        ;
        */

        $collection->addAttributeToSelect(
            array(
                'name',
                'price',
                'small_image', // exists in collection when Flat Product is enabled
            )
        );
        $collection
            ->joinField(
                'is_in_stock',
                'cataloginventory/stock_item',
                'is_in_stock',
                'product_id=entity_id',
                '{{table}}.stock_id=1',
                'left'
            )
            ->addAttributeToFilter('is_in_stock', 0)
            // TODO: investigate qty = 0
//            ->joinField(
//                'qty',
//                'cataloginventory/stock_item',
//                'qty',
//                'product_id=entity_id',
//                '{{table}}.stock_id=1',
//                'left'
//            )
//            ->addAttributeToFilter('qty', array('eq' => 0))
        ;
        return $collection;
    }

    public function generateReportStats()
    {
        $directoryPath = Mage::getBaseDir('var') . DS . self::VAR_REPORT . DS;
        if (!is_dir($directoryPath)) {
            return false;
        }

        $count = 0;
        $directoryIterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directoryPath));
        $files = array();
        foreach ($directoryIterator as $_file) {
            /** @var SplFileInfo $_file */
            if (!$_file->isFile()) {
                continue;
            }
            $files[$_file->getMTime()][$_file->getFilename()] = $_file;
            $count++;
        }
        ksort($files);
        return Mage::getSingleton('neklo_monitor/minfo_report')->generateReports($files);
    }

    public function generateLogStats($type)
    {
        return Mage::getSingleton('neklo_monitor/minfo_log')->generateLogs($type);
    }

    protected function _format($bytes)
    {
        $exp = (int)floor(log($bytes) / log(1024));
        $symbols = array('B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB');
        return sprintf('%.2f ' . $symbols[$exp], ($bytes / pow(1024, floor($exp))));
    }
}