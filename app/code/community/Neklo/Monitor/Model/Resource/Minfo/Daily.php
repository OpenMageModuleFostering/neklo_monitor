<?php


class Neklo_Monitor_Model_Resource_Minfo_Daily extends Mage_Sales_Model_Mysql4_Report_Abstract
{
    protected function _construct()
    {
        $this->_init('sales/order_aggregated_created', 'id');
    }

    public function collect()
    {
        $dateEnd = new Zend_Date(Mage::getModel('core/date')->gmtTimestamp());
        $dateStart = clone $dateEnd;

        // go to the end of the previous day
        $dateEnd->subDay(1);
        $dateEnd->setHour(23);
        $dateEnd->setMinute(59);
        $dateEnd->setSecond(59);

        // go to the beginning of the previous day
        $dateStart->subDay(1);
        $dateStart->setHour(0);
        $dateStart->setMinute(0);
        $dateStart->setSecond(0);


        $report = array(
            'orders' => array(
                'all' => array(
                    'orders_count' => 0,
                    'subtotal_sum' => $this->_convertPrice(0),
                    'subtotal_avg' => $this->_convertPrice(0),
                    'revenue_sum' => $this->_convertPrice(0),
                    'revenue_avg' => $this->_convertPrice(0),
                    'items_qty_sum' => 0,
                    'items_qty_avg' => 0,
                ),
                'newcustomers' => array(
                    'orders_count' => 0,
                    'subtotal_sum' => $this->_convertPrice(0),
                    'subtotal_avg' => $this->_convertPrice(0),
                    'revenue_sum' => $this->_convertPrice(0),
                    'revenue_avg' => $this->_convertPrice(0),
                    'items_qty_sum' => 0,
                    'items_qty_avg' => 0,
                ),
                'oldcustomers' => array(
                    'orders_count' => 0,
                    'subtotal_sum' => $this->_convertPrice(0),
                    'subtotal_avg' => $this->_convertPrice(0),
                    'revenue_sum' => $this->_convertPrice(0),
                    'revenue_avg' => $this->_convertPrice(0),
                    'items_qty_sum' => 0,
                    'items_qty_avg' => 0,
                ),
                'guests' => array(
                    'orders_count' => 0,
                    'subtotal_sum' => $this->_convertPrice(0),
                    'subtotal_avg' => $this->_convertPrice(0),
                    'revenue_sum' => $this->_convertPrice(0),
                    'revenue_avg' => $this->_convertPrice(0),
                    'items_qty_sum' => 0,
                    'items_qty_avg' => 0,
                ),
            ),
            'newcustomers_count' => 0,
            'from' => $dateStart->toString(Varien_Date::DATETIME_INTERNAL_FORMAT),
            'to'   => $dateEnd->toString(Varien_Date::DATETIME_INTERNAL_FORMAT),
        );


        /** @var Neklo_Monitor_Model_Resource_Minfo_Daily_ReportsOrderCollection $totalData */
        $totalData = Mage::getResourceModel('neklo_monitor/minfo_daily_reportsOrderCollection');
        $totalData->calculateDailyReport('custom', $dateStart, $dateEnd, false);
        foreach ($totalData as $_data) {
            $report['orders']['all'] = $this->_fillInDataArray($_data);
        }


        /** @var Neklo_Monitor_Model_Resource_Minfo_Daily_ReportsOrderCollection $splitData */
        $splitData = Mage::getResourceModel('neklo_monitor/minfo_daily_reportsOrderCollection');
        $splitData->calculateDailyReport('custom', $dateStart, $dateEnd, true);
        foreach ($splitData as $_data) {
            $report['orders'][ $_data->getData('customer_type') ] = $this->_fillInDataArray($_data);
        }


        /** @var Mage_Customer_Model_Entity_Customer_Collection $collCustomers */
        $collCustomers = Mage::getResourceModel('customer/customer_collection');
        $collCustomers->addAttributeToFilter('created_at', array('from' => $dateStart, 'to' => $dateEnd, 'datetime' => true));
        $report['newcustomers_count'] = $collCustomers->getSize();

        return $report;
    }

    protected function _fillInDataArray($_data)
    {
        return array(
            'orders_count'  => $_data->getData('orders_count') * 1,
            'subtotal_sum'  => $this->_convertPrice($_data->getData('orders_sum_amount')),
            'subtotal_avg'  => $this->_convertPrice($_data->getData('orders_avg_amount')),
            'revenue_sum'   => $this->_convertPrice($_data->getData('revenue_sum')),
            'revenue_avg'   => $this->_convertPrice($_data->getData('revenue_avg')),
            'items_qty_sum' => $_data->getData('items_qty_sum') * 1,
            'items_qty_avg' => $_data->getData('items_qty_avg') * 1,
        );
    }

    protected function _convertPrice($value)
    {
        return Mage::app()->getStore(Mage_Core_Model_Store::ADMIN_CODE)->convertPrice($value, true, false);
    }
}