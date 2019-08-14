<?php

class Neklo_Monitor_DashboardController extends Neklo_Monitor_Controller_Abstract
{
    public function totalAction()
    {
        if (Mage::helper('core')->isModuleEnabled('Mage_Reports')) {

            $isFilter = false;
            $storeId = $this->_getRequestHelper()->getParam('store', null);
            if ($storeId) {
                $isFilter = true;
            }
            /* @var $collection Mage_Reports_Model_Mysql4_Order_Collection */
            $collection = Mage::getResourceModel('reports/order_collection');
            $collection->calculateSales($isFilter);
            if ($storeId) {
                $collection->addFieldToFilter('store_id', (int)$storeId);
            }

            $collection->setPageSize(1);
            $salesStats = $collection->getFirstItem();

            $result = array(
                'lifetime' => Mage::app()->getStore($storeId)->convertPrice($salesStats->getLifetime(), true, false),
                'average'  => Mage::app()->getStore($storeId)->convertPrice($salesStats->getAverage(), true, false),
            );

            foreach (array('24h', '1m') as $period) {
                /* @var $collection Mage_Reports_Model_Mysql4_Order_Collection */
                $collection = Mage::getResourceModel('reports/order_collection');
                $collection
                    ->addCreateAtPeriodFilter($period)
                    ->calculateTotals($isFilter);
                if ($storeId) {
                    $collection->addFieldToFilter('store_id', (int)$storeId);
                }

                $collection->setPageSize(1);
                $salesStats = $collection->getFirstItem();

                $result['period'.$period] = Mage::app()->getStore($storeId)->convertPrice($salesStats->getRevenue(), true, false);
            }

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
        $productsData = array();
        foreach ($productCollection as $row) {
            /** @var Mage_Catalog_Model_Product $row */
            $productsData[$row->getId()] = $row->getData();
            $productsData[$row->getId()] += Mage::helper('neklo_monitor')->resizeProductImage($row, 'small_image');
        }

        $result = array('result' => array());
        // the aggregated_yearly data fetched by $collection
        foreach ($collection as $row) {
            $_prodId = $row->getData('product_id');
            if (isset($productsData[$_prodId])) {
                $prodData = $productsData[$_prodId];
                $reportItem = array(
                    'id'    => $row->getData('product_id'),
                    'name'  => $row->getData('product_name'),
                    'price' => Mage::app()->getStore($storeId)->convertPrice($row->getData('product_price'), true, false),
                    'sku'   => $prodData['sku'],
                    'qty'   => (int)$row->getData('qty_ordered'),
                    'image2xUrl' => $prodData['image2xUrl'],
                    'image3xUrl' => $prodData['image3xUrl'],
                );
                // actually no images in simple products, and placeholders are generated into 'image2xUrl' 'image3xUrl'
                if ('simple' == $prodData['type_id']
                    && (empty($prodData['small_image']) || 'no_selection' == $prodData['small_image'])) {
                    // search for any order item with this product during the period (a year based on report data)
                    // to find a configurable parent, then load its product to get its image
                    $orderItems = Mage::getResourceModel('sales/order_item_collection');
                    $_from = $row['period'] . ' 00:00:00';
                    $_toTs = strtotime($row['period']) + 365*24*60*60; // + 1 year, as the report is fetched from aggregated_yearly table
                    $_to = date('Y-m-d', $_toTs) . ' 00:00:00';
                    $orderItems
                        ->addFieldToFilter('created_at', array('from' => $_from, 'to' => $_to))
                        ->addFieldToFilter('product_id', $_prodId)
                        ->addFieldToFilter('product_type', 'simple')
                        ->addFieldToFilter('parent_item_id', array('gt' => 0))
                        ->setOrder('created_at', 'desc')
                        ->setPageSize(1);
                    $orderItem = $orderItems->getFirstItem();

                    $parentOrderItem = Mage::getModel('sales/order_item')->load($orderItem->getParentItemId());
                    $parentProd = Mage::getModel('catalog/product')->load($parentOrderItem->getProductId());
                    if ($orderItem && $orderItem->getId() && $parentOrderItem->getId() && $parentProd->getId()) {
                        $parentImages = Mage::helper('neklo_monitor')->resizeProductImage($parentProd, 'small_image');
                        $reportItem['image2xUrl'] = $parentImages['image2xUrl'];
                        $reportItem['image3xUrl'] = $parentImages['image3xUrl'];
                    }
                }
                $result['result'][] = $reportItem;
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

        $result = array('result' => array());
        foreach ($collection as $row) {
            /** @var Mage_Catalog_Model_Product $row */
            $listItem = array(
                'id'    => $row->getEntityId(),
                'name'  => $row->getName(),
                'price' => Mage::app()->getStore($storeId)->convertPrice($row->getPrice(), true, false),
                'sku'   => $row->getSku(),
                'views' => (int)$row->getData('views'),
            );
            $listItem += Mage::helper('neklo_monitor')->resizeProductImage($row, 'small_image');
            $result['result'][] = $listItem;
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

        $result = array('result' => array());
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

            $result['result'][] = $customerData;
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
            $collection->addAttributeToFilter('main_table.store_id', $storeId);
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

        $result = array('result' => array());
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

            $result['result'][] = $customerData;
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

        $result = array('result' => array());
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

            $result['result'][] = $orderData;
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
            $collection->addFieldToFilter('store_id', $storeId);
        }

        $collection->setPageSize(5);
        $collection->setCurPage(1);

        $result = array('result' => array());
        foreach ($collection as $row) {
            $searchData = array(
                'id'                => $row->getData('query_id'),
                'query'             => $row->getData('query_text'),
                'number_of_uses'    => $row->getData('popularity'),
                'number_of_results' => $row->getData('num_results'),
                'last_usage'        => Mage::helper('neklo_monitor/date')->convertToTimestamp($row->getData('updated_at')),
            );
            $result['result'][] = $searchData;
        }

        $this->_jsonResult($result);
    }

    public function topsearchesAction()
    {
        /* @var $collection Mage_CatalogSearch_Model_Mysql4_Query_Collection */
        $collection = Mage::getResourceModel('catalogsearch/query_collection');

        $storeId = $this->_getRequestHelper()->getParam('store', '');
        $collection->setPopularQueryFilter($storeId);
        $collection->getSelect()->columns('main_table.updated_at');

        $collection->setPageSize(5);
        $collection->setCurPage(1);

        $result = array('result' => array());
        foreach ($collection as $row) {
            $searchData = array(
                'query'             => $row->getData('name'),
                'number_of_uses'    => $row->getData('popularity'),
                'number_of_results' => $row->getData('num_results'),
                'last_usage'        => Mage::helper('neklo_monitor/date')->convertToTimestamp($row->getData('updated_at')),
            );
            $result['result'][] = $searchData;
        }

        $this->_jsonResult($result);
    }

    public function chartAction()
    {
        /** @var Mage_Adminhtml_Helper_Dashboard_Order $chartHelper */
        $chartHelper = Mage::helper('adminhtml/dashboard_order');

        $isFilter = false;
        $storeId = $this->_getRequestHelper()->getParam('store', null);
        if ($storeId) {
            $chartHelper->setParam('store', $storeId);
            $isFilter = true;
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

        /* @var $chartCollection Mage_Reports_Model_Mysql4_Order_Collection */
        $chartCollection = $chartHelper->getCollection();

        $originalChart = array();
        foreach ($chartCollection->getItems() as $item) {
            $originalChart[ $item->getData('range') ] = (float)$item->getData($chartType);
        }

        /** @var Zend_Date $dateStart */
        /** @var Zend_Date $dateEnd */

        // fill empty x-axis points with 0 values
        list ($dateStart, $dateEnd) = $chartCollection->getDateRange($period, '', '', true);
        while ($dateStart->compare($dateEnd) < 0) {
            $_range = $dateStart->toString($periodMask);
            if (!array_key_exists($_range, $originalChart)) {
                $originalChart[$_range] = 0;
            }

            // move to next x-axis point
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
        }

        $chartData = array();
        foreach ($originalChart as $_range => $_value) {
            $zDate = new Zend_Date($_range, $periodMask);
            $_ts = $zDate->getTimestamp();
            $chartData[$_ts] = $_value;
        }
        ksort($chartData);

        $result = array(
            'chart' => array(),
            'total' => array(
                'revenue' => '0',
                'qty'     => 0,
            ),
        );
        foreach ($chartData as $date => $value) {
            $result['chart'][] = array(
                'date'  => $date,
//                'date_hf'  => date('Y-m-d H:i:s', $date),
                'value' => $value,
            );
        }
        /*
        foreach ($originalChart as $date => $value) {
            $result['o-chart'][] = array(
                'date'  => $date,
                'value' => $value,
            );
        }
        */

        /* @var $collection Mage_Reports_Model_Mysql4_Order_Collection */
        $collection = Mage::getResourceModel('reports/order_collection');
        $collection->addCreateAtPeriodFilter($period);
        $collection->calculateTotals($isFilter);
        if ($storeId) {
            $collection->addFieldToFilter('store_id', (int)$storeId);
        } else if (!$collection->isLive()) {
            $collection->addFieldToFilter('store_id',
                array('eq' => Mage::app()->getStore(Mage_Core_Model_Store::ADMIN_CODE)->getId())
            );
        }
        $collection->setPageSize(1);
        $collection->load();
        $salesStats = $collection->getFirstItem();
        $result['total']['revenue'] = Mage::app()->getStore($storeId)->convertPrice($salesStats->getRevenue(), true, false);
        $result['total']['qty']     = $salesStats->getQuantity() * 1;

        $this->_jsonResult($result);
    }

}
