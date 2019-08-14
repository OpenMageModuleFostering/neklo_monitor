<?php

class Neklo_Monitor_DashboardController extends Neklo_Monitor_Controller_Abstract
{
    public function totalAction()
    {
        if (Mage::helper('core')->isModuleEnabled('Mage_Reports')) {
            /* @var $collection Mage_Reports_Model_Mysql4_Order_Collection */
            $collection = Mage::getResourceModel('reports/order_collection');
            $collection->calculateSales(false);

            $storeId = $this->_getRequestHelper()->getParam('store', null);
            if ($storeId) {
                $collection->addFieldToFilter('store_id', (int)$storeId);
            }

            $collection->setPageSize(1);
            $collection->setCurPage(1);

            $collection->load();
            $salesStats = $collection->getFirstItem();

            $result = array(
                'lifetime' => Mage::app()->getStore($storeId)->convertPrice($salesStats->getLifetime(), true, false),
                'average'  => Mage::app()->getStore($storeId)->convertPrice($salesStats->getAverage(), true, false),
            );
        } else {
            $result = array();
        }

        $this->_jsonResult($result);
    }

    public function bestsellerAction()
    {
        /* @var $collection Mage_Sales_Model_Mysql4_Report_Bestsellers_Collection */
        $collection = Mage::getResourceModel('sales/report_bestsellers_collection')
            ->setModel('catalog/product')
        ;

        $storeId = $this->_getRequestHelper()->getParam('store', null);
        if ($storeId) {
            $collection->addStoreFilter((int)$storeId);
        }

        $collection->setPageSize(5);
        $collection->setCurPage(1);

        $productIdList = $collection->getColumnValues('product_id');

        /* @var $productCollection Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection */
        $productCollection = Mage::getResourceModel('catalog/product_collection');
        $productCollection->addFieldToFilter('entity_id', array('in' => $productIdList));
        $productCollection->addAttributeToSelect(
            array(
                'sku', // exists in collection when Flat Product is enabled
                'small_image', // exists in collection when Flat Product is enabled
            )
        );
        $skuList = array();
        $thumbList = array();
        $hlp = Mage::helper('catalog/image');
        /** @var Mage_Catalog_Helper_Image $hlp */
        foreach ($productCollection as $row) {
            /** @var Mage_Catalog_Model_Product $row */
            $skuList[$row->getId()] = $row->getSku();

            $hlp->init($row, 'small_image');
            $thumbList[$row->getId()] = array(
                'image2xUrl' => $hlp->resize(224, 300)->__toString(),
                'image3xUrl' => $hlp->resize(336, 450)->__toString(),
            );
        }

        $result = array();
        foreach ($collection as $row) {
            if (isset($skuList[$row->getData('product_id')])) {
                $result[] = array(
                    'id'    => $row->getData('product_id'),
                    'name'  => $row->getData('product_name'),
                    'price' => Mage::app()->getStore($storeId)->convertPrice($row->getData('product_price'), true, false),
                    'sku'   => $skuList[$row->getData('product_id')],
                    'qty'   => (int)$row->getData('qty_ordered'),
                    'image2xUrl' => $thumbList[$row->getData('product_id')]['image2xUrl'],
                    'image3xUrl' => $thumbList[$row->getData('product_id')]['image3xUrl'],
                );
            }
        }

        $this->_jsonResult($result);
    }

    public function mostviewedAction()
    {
        /* @var $collection Mage_Reports_Model_Mysql4_Product_Collection */
        $collection = Mage::getResourceModel('reports/product_collection')
            ->addAttributeToSelect(
                array(
                    'price',
                    'name',
                    'small_image',
                )
            )
            ->addViewsCount()
        ;

        $storeId = $this->_getRequestHelper()->getParam('store', null);
        if ($storeId) {
            $collection
                ->setStoreId((int)$storeId)
                ->addStoreFilter((int)$storeId)
            ;
        }
        $collection->setPageSize(5);
        $collection->setCurPage(1);
        $collection->load();

        $hlp = Mage::helper('catalog/image');
        /** @var Mage_Catalog_Helper_Image $hlp */
        $result = array();
        foreach ($collection as $row) {
            /** @var Mage_Catalog_Model_Product $row */
            $hlp->init($row, 'small_image');
            $result[] = array(
                'id'    => $row->getEntityId(),
                'name'  => $row->getName(),
                'price' => Mage::app()->getStore($storeId)->convertPrice($row->getPrice(), true, false),
                'sku'   => $row->getSku(),
                'views' => (int)$row->getData('views'),
                'image2xUrl' => $hlp->resize(224, 300)->__toString(),
                'image3xUrl' => $hlp->resize(336, 450)->__toString(),
            );
        }

        $this->_jsonResult($result);
    }

    public function newcustomersAction()
    {
        /* @var $collection Mage_Reports_Model_Mysql4_Customer_Collection */
        $collection = Mage::getResourceModel('reports/customer_collection')->addCustomerName();
        $storeFilter = 0;
        $storeId = $this->_getRequestHelper()->getParam('store', null);
        if ($storeId) {
            $collection->addAttributeToFilter('store_id', $storeId);
            $storeFilter = 1;
        }
        $collection->addOrdersStatistics($storeFilter);
        $collection->orderByCustomerRegistration();
        $collection->setPageSize(5);
        $collection->setCurPage(1);
        $collection->load();

        $groupList = Mage::getResourceModel('customer/group_collection')
            ->addFieldToFilter('customer_group_id', array('gt' => 0))
            ->load()
            ->toOptionHash()
        ;

        $result = array();
        foreach ($collection as $row) {

            if ((array_key_exists($row->getData('group_id'), $groupList))) {
                $customerGroup = $groupList[$row->getData('group_id')];
            } else {
                $customerGroup = 'N/A';
            }

            $customerData = array(
                'id'                   => $row->getData('entity_id'),
                'email'                => $row->getData('email'),
                'name'                 => $row->getData('name'),
                'created_at'           => Mage::helper('neklo_monitor/date')->convertToTimestamp($row->getData('created_at')),
                'group'                => $customerGroup,
                'average_order_amount' => Mage::app()->getStore($storeId)->convertPrice($row->getData('orders_avg_amount'), true, false),
                'total_order_amount'   => Mage::app()->getStore($storeId)->convertPrice($row->getData('orders_sum_amount'), true, false),
                'order_count'          => (int)$row->getData('orders_count'),
            );

            $result[] = $customerData;
        }

        $this->_jsonResult($result);
    }

    public function topcustomersAction()
    {
        /* @var $collection Mage_Reports_Model_Mysql4_Order_Collection */
        $collection = Mage::getResourceModel('reports/order_collection');
        $collection
            ->groupByCustomer()
            ->addOrdersCount()
            ->joinCustomerName()
        ;
        $storeFilter = 0;
        $storeId = $this->_getRequestHelper()->getParam('store', null);
        if ($storeId) {
            $collection->addAttributeToFilter('store_id', $storeId);
            $storeFilter = 1;
        }
        $collection
            ->addSumAvgTotals($storeFilter)
            ->orderByTotalAmount()
        ;

        $collection->getSelect()->joinLeft(
            array('customer' => $collection->getTable('customer/entity')),
            'main_table.customer_id = customer.entity_id',
            array('customer_created_at' => 'customer.created_at')
        );

        $groupList = Mage::getResourceModel('customer/group_collection')
            ->addFieldToFilter('customer_group_id', array('gt' => 0))
            ->load()
            ->toOptionHash()
        ;

        $collection->setPageSize(5);
        $collection->setCurPage(1);

        $result = array();
        foreach ($collection as $row) {
            if ((array_key_exists($row->getData('customer_group_id'), $groupList))) {
                $customerGroup = $groupList[$row->getData('customer_group_id')];
            } else {
                $customerGroup = 'N/A';
            }

            $customerData = array(
                'id'                   => $row->getData('customer_id'),
                'email'                => $row->getData('customer_email'),
                'name'                 => $row->getData('name'),
                'created_at'           => Mage::helper('neklo_monitor/date')->convertToTimestamp($row->getData('customer_created_at')),
                'group'                => $customerGroup,
                'average_order_amount' => Mage::app()->getStore($storeId)->convertPrice($row->getData('orders_avg_amount'), true, false),
                'total_order_amount'   => Mage::app()->getStore($storeId)->convertPrice($row->getData('orders_sum_amount'), true, false),
                'order_count'          => (int)$row->getData('orders_count'),
            );

            $result[] = $customerData;
        }

        $this->_jsonResult($result);
    }

    public function lastordersAction()
    {
        /* @var $collection Mage_Reports_Model_Mysql4_Order_Collection */
        $collection = Mage::getResourceModel('reports/order_collection')
            ->addItemCountExpr()
            ->joinCustomerName('customer')
            ->orderByCreatedAt()
        ;

        $storeId = $this->_getRequestHelper()->getParam('store', null);
        if ($storeId) {
            $collection->addAttributeToFilter('store_id', $storeId);
            $collection->addRevenueToSelect();
        } else {
            $collection->addRevenueToSelect(true);
        }

        $collection->setPageSize(5);
        $collection->setCurPage(1);

        $orderStatusList = Mage::getSingleton('sales/order_config')->getStatuses();

        $groupList = Mage::getResourceModel('customer/group_collection')
            ->addFieldToFilter('customer_group_id', array('gt' => 0))
            ->load()
            ->toOptionHash()
        ;

        $result = array();
        foreach ($collection as $row) {
            if ((array_key_exists($row->getData('status'), $orderStatusList))) {
                $orderStatus = $orderStatusList[$row->getData('status')];
            } else {
                $orderStatus = 'N/A';
            }

            if ((array_key_exists($row->getData('customer_group_id'), $groupList))) {
                $customerGroup = $groupList[$row->getData('customer_group_id')];
            } else {
                $customerGroup = 'N/A';
            }

            $orderData = array(
                'id'             => $row->getData('entity_id'),
                'increment_id'   => $row->getData('increment_id'),
                'created_at'     => Mage::helper('neklo_monitor/date')->convertToTimestamp($row->getData('created_at')),
                'status'         => $orderStatus,
                'grand_total'    => Mage::app()->getStore($storeId)->convertPrice($row->getData('revenue'), true, false),
                'items_count'    => (int)$row->getData('items_count'),
                'customer' => array(
                    'id'    => $row->getData('customer_id'),
                    'email' => $row->getData('customer_email'),
                    'name'  => $row->getData('customer'),
                    'group' => $customerGroup,
                ),
            );

            $result[] = $orderData;
        }

        $this->_jsonResult($result);
    }

    public function lastsearchesAction()
    {
        /* @var $collection Mage_CatalogSearch_Model_Mysql4_Query_Collection */
        $collection = Mage::getResourceModel('catalogsearch/query_collection');
        $collection->setRecentQueryFilter();

        $storeId = $this->_getRequestHelper()->getParam('store', null);
        if ($storeId) {
            $collection->addAttributeToFilter('store_id', $storeId);
        }

        $collection->setPageSize(5);
        $collection->setCurPage(1);

        $result = array();
        foreach ($collection as $row) {
            $searchData = array(
                'id'                => $row->getData('query_id'),
                'query'             => $row->getData('query_text'),
                'number_of_uses'    => $row->getData('popularity'),
                'number_of_results' => $row->getData('num_results'),
                'last_usage'        => Mage::helper('neklo_monitor/date')->convertToTimestamp($row->getData('updated_at')),
            );
            $result[] = $searchData;
        }

        $this->_jsonResult($result);
    }

    public function topsearchesAction()
    {
        /* @var $collection Mage_CatalogSearch_Model_Mysql4_Query_Collection */
        $collection = Mage::getResourceModel('catalogsearch/query_collection');

        $storeId = $this->_getRequestHelper()->getParam('store', '');
        $collection->setPopularQueryFilter($storeId);

        $collection->setPageSize(5);
        $collection->setCurPage(1);

        $result = array();
        foreach ($collection as $row) {
            $searchData = array(
                'query'             => $row->getData('name'),
                'number_of_uses'    => $row->getData('popularity'),
                'number_of_results' => $row->getData('num_results'),
                'last_usage'        => Mage::helper('neklo_monitor/date')->convertToTimestamp($row->getData('updated_at')),
            );
            $result[] = $searchData;
        }

        $this->_jsonResult($result);
    }

    public function chartAction()
    {
        $chartHelper = Mage::helper('adminhtml/dashboard_order');

        $storeId = $this->_getRequestHelper()->getParam('store', null);
        if ($storeId) {
            $chartHelper->setParam('store', $storeId);
        }

        $chartType = $this->_getRequestHelper()->getParam('type', 'quantity');
        if (!$chartType || !in_array($chartType, array('quantity', 'revenue'))) {
            $chartType = 'quantity';
        }

        $availablePeriodList = Mage::helper('adminhtml/dashboard_data')->getDatePeriods();
        $period = $this->_getRequestHelper()->getParam('period', '24h');
        if (!$period || !in_array($period, array_keys($availablePeriodList))) {
            $period = '24h';
        }
        $chartHelper->setParam('period', $period);
        switch ($period) {
            case '24h':
                $periodMask = 'yyyy-MM-dd HH:00';
                break;
            case '7d':
            case '1m':
                $periodMask = 'yyyy-MM-dd';
                break;
            case '1y':
            case '2y':
                $periodMask = 'yyyy-MM';
                break;
        }

        $chartData = array();
        $items = $chartHelper->getCollection()->getItems();
        foreach ($items as $item) {
            $zDate = new Zend_Date($item->getData('range'), $periodMask);
            $chartData[$zDate->getTimestamp()] = (float)$item->getData($chartType);
        }

        list ($dateStart, $dateEnd) = Mage::getResourceModel('reports/order_collection')->getDateRange($period, '', '', true);
        while ($dateStart->compare($dateEnd) < 0) {
            $timestamp = $dateStart->getTimestamp();
            switch ($period) {
                case '24h':
                    $dateStart->addHour(1);
                    break;
                case '7d':
                case '1m':
                    $dateStart->addDay(1);
                    break;
                case '1y':
                case '2y':
                    $dateStart->addMonth(1);
                    break;
            }
            if (!array_key_exists($timestamp, $chartData)) {
                $chartData[$timestamp] = 0;
            }
        }
        ksort($chartData);

        $result = array();
        foreach ($chartData as $date => $value) {
            $result[] = array(
                'date'  => $date,
                'value' => $value,
            );
        }

        $this->_jsonResult($result);
    }

}
