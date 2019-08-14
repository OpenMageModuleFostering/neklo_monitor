<?php

class Neklo_Monitor_Report_SalesController extends Neklo_Monitor_Controller_Abstract
{
    // @see Mage_Adminhtml_Block_Report_Grid_Abstract::_prepareCollection()
    protected function _prepareResourceCollection($resourceCollectionName)
    {
        /** @var Neklo_Monitor_Helper_Date $hlpDate */
        $hlpDate = Mage::helper('neklo_monitor/date');

        $storeIds = $this->_getRequestHelper()->getParam('store', null);
        $periodType = $this->_getRequestHelper()->getParam('group', null);

        $fromTimestamp = $this->_getRequestHelper()->getParam('from', null);
        $fromDate = $hlpDate->convertToString($fromTimestamp);

        $toTimestamp = $this->_getRequestHelper()->getParam('to', null);
        $toDate = $hlpDate->convertToString($toTimestamp);

        $orderStatuses = $this->_getRequestHelper()->getParam('status', null);

        /** @var Mage_Sales_Model_Mysql4_Report_Collection_Abstract $resourceCollection */
        $resourceCollection = Mage::getResourceModel($resourceCollectionName);
        $resourceCollection
            ->setPeriod($periodType)
            ->setDateRange($fromDate, $toDate)
            ->addStoreFilter(explode(',', $storeIds))
            ->addOrderStatusFilter($orderStatuses)
//            ->setAggregatedColumns($this->_getAggregatedColumns())
        ;

        /* if 'show_empty_rows'
        Mage::helper('reports')->prepareIntervalsCollection(
            $mainCollection,
            $fromDate,
            $toDate,
            $periodType
        );
        */

        /** @var Mage_Sales_Model_Mysql4_Report_Collection_Abstract $totalsCollection */
        $totalsCollection = Mage::getResourceModel($resourceCollectionName);
        $totalsCollection
            ->setPeriod($periodType)
            ->setDateRange($fromDate, $toDate)
            ->addStoreFilter(explode(',', $storeIds))
            ->addOrderStatusFilter($orderStatuses)
//            ->setAggregatedColumns($this->_getAggregatedColumns())
            ->isTotals(true);

        return array($resourceCollection, $totalsCollection);
    }

    /**
     * @param Mage_Sales_Model_Mysql4_Report_Collection_Abstract $resourceCollection
     * @param Mage_Sales_Model_Mysql4_Report_Collection_Abstract $totalsCollection
     * @param array $aggregatedColumns
     * @return array
     */
    protected function _fetchReport($resourceCollection, $totalsCollection, $aggregatedColumns = array())
    {
        /** @var Mage_Reports_Model_Grouped_Collection $mainCollection */
        $mainCollection = Mage::getModel('reports/grouped_collection');
        $mainCollection->setColumnGroupBy('period');
        $mainCollection->setResourceCollection($resourceCollection);

        $mainCollection->load();

        $list = array(
            'report' => array(),
            'totals' => false,
        );
        foreach ($mainCollection->getItems() as $_period => $_item) {
            /** @var Mage_Adminhtml_Model_Report_Item $_item */
            $list['report'][] = $_item->getData();
        }

        if ($aggregatedColumns) {
            $totalsCollection->setAggregatedColumns($aggregatedColumns);
            $totalsCollection->load();
            foreach ($totalsCollection as $_item) {
                $list['totals'] = $_item->getData();
                break;
            }
        }

        return $list;
    }

    public function orderAction()
    {
        $resourceCollectionName = 'sales/report_order_updatedat_collection';
//        $resourceCollectionName = 'sales/report_order_collection';
        list($resourceCollection, $totalsCollection) = $this->_prepareResourceCollection($resourceCollectionName);
        $list = $this->_fetchReport($resourceCollection, $totalsCollection, array(
            'orders_count' => 'SUM(orders_count)',
            'total_qty_ordered' => 'SUM(total_qty_ordered)',
            'total_qty_invoiced' => 'SUM(total_qty_invoiced)',
            'total_income_amount' => 'SUM(total_income_amount)',
            'total_revenue_amount' => 'SUM(total_revenue_amount)',
            'total_profit_amount' => 'SUM(total_profit_amount)',
            'total_invoiced_amount' => 'SUM(total_invoiced_amount)',
            'total_canceled_amount' => 'SUM(total_canceled_amount)',
            'total_paid_amount' => 'SUM(total_paid_amount)',
            'total_refunded_amount' => 'SUM(total_refunded_amount)',
            'total_tax_amount' => 'SUM(total_tax_amount)',
            'total_tax_amount_actual' => 'SUM(total_tax_amount_actual)',
            'total_shipping_amount' => 'SUM(total_shipping_amount)',
            'total_shipping_amount_actual' => 'SUM(total_shipping_amount_actual)',
            'total_discount_amount' => 'SUM(total_discount_amount)',
            'total_discount_amount_actual' => 'SUM(total_discount_amount_actual)',
        ));
        $this->_jsonResult($list);
    }

    public function taxAction()
    {
        $resourceCollectionName = 'tax/report_updatedat_collection';
//        $resourceCollectionName = 'tax/report_collection';
        list($resourceCollection, $totalsCollection) = $this->_prepareResourceCollection($resourceCollectionName);
        $list = $this->_fetchReport($resourceCollection, $totalsCollection, array(
            'orders_count' => 'SUM(orders_count)',
            'tax_base_amount_sum' => 'SUM(tax_base_amount_sum)',
        ));
        $this->_jsonResult($list);
    }

    public function invoicedAction()
    {
        $resourceCollectionName = 'sales/report_invoiced_collection_invoiced';
//        $resourceCollectionName = 'sales/report_invoiced_collection_order';
        list($resourceCollection, $totalsCollection) = $this->_prepareResourceCollection($resourceCollectionName);
        $list = $this->_fetchReport($resourceCollection, $totalsCollection, array(
            'orders_count' => 'SUM(orders_count)',
            'orders_invoiced' => 'SUM(orders_invoiced)',
            'invoiced' => 'SUM(invoiced)',
            'invoiced_captured' => 'SUM(invoiced_captured)',
            'invoiced_not_captured' => 'SUM(invoiced_not_captured)',
        ));
        $this->_jsonResult($list);
    }

    public function shippingAction()
    {
        $resourceCollectionName = 'sales/report_shipping_collection_shipment';
//        $resourceCollectionName = 'sales/report_shipping_collection_order';
        list($resourceCollection, $totalsCollection) = $this->_prepareResourceCollection($resourceCollectionName);
        $list = $this->_fetchReport($resourceCollection, $totalsCollection, array(
            'orders_count' => 'SUM(orders_count)',
            'total_shipping' => 'SUM(total_shipping)',
            'total_shipping_actual' => 'SUM(total_shipping_actual)',
        ));
        $this->_jsonResult($list);
    }

    public function refundedAction()
    {
        $resourceCollectionName = 'sales/report_refunded_collection_refunded';
        // $resourceCollectionName = 'sales/report_refunded_collection_order';
        list($resourceCollection, $totalsCollection) = $this->_prepareResourceCollection($resourceCollectionName);
        $list = $this->_fetchReport($resourceCollection, $totalsCollection, array(
            'orders_count' => 'SUM(orders_count)',
            'refunded' => 'SUM(refunded)',
            'online_refunded' => 'SUM(online_refunded)',
            'offline_refunded' => 'SUM(offline_refunded)',
        ));
        $this->_jsonResult($list);
    }

    public function couponsAction()
    {
        $resourceCollectionName = 'salesrule/report_updatedat_collection';
        // $resourceCollectionName = 'salesrule/report_collection';
        list($resourceCollection, $totalsCollection) = $this->_prepareResourceCollection($resourceCollectionName);
        $list = $this->_fetchReport($resourceCollection, $totalsCollection, array(
            'coupon_uses' => 'SUM(coupon_uses)',
            'subtotal_amount' => 'SUM(subtotal_amount)',
            'discount_amount' => 'SUM(discount_amount)',
            'total_amount' => 'SUM(total_amount)',
            'subtotal_amount_actual' => 'SUM(subtotal_amount_actual)',
            'discount_amount_actual' => 'SUM(discount_amount_actual)',
            'total_amount_actual' => 'SUM(total_amount_actual)',
        ));
        $this->_jsonResult($list);
    }

}