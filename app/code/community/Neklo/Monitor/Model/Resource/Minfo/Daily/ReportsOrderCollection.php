<?php

class Neklo_Monitor_Model_Resource_Minfo_Daily_ReportsOrderCollection extends Mage_Reports_Model_Mysql4_Order_Collection
{
    public function calculateDailyReport($range, $customStart, $customEnd, $groupByCustomers = false)
    {
        list($from, $to) = $this->getDateRange($range, $customStart, $customEnd, true);
        $from = $from->toString(Varien_Date::DATETIME_INTERNAL_FORMAT);
        $to   = $to->toString(Varien_Date::DATETIME_INTERNAL_FORMAT);

        $this->setMainTable('sales/order');

        $this->getSelect()->reset(Zend_Db_Select::COLUMNS);
        $this->removeAllFieldsFromSelect();

        $this->_addRevenueExpression();

/*
        $subtotalExpr = vsprintf(
            '(main_table.base_subtotal - %s - %s) * main_table.base_to_global_rate',
            array(
                new Zend_Db_Expr(sprintf("IFNULL(%s, %s)", 'main_table.base_subtotal_refunded', 0)),
                new Zend_Db_Expr(sprintf("IFNULL(%s, %s)", 'main_table.base_subtotal_canceled', 0)),
            )
        );
        $this->getSelect()->columns(array(
            'orders_subtotal' => new Zend_Db_Expr(sprintf('SUM(%s)', $subtotalExpr)),
        ));
*/

/*
        $shippingExpr = vsprintf(
            '(main_table.base_shipping_amount - %s - %s) * main_table.base_to_global_rate',
            array(
                new Zend_Db_Expr(sprintf("IFNULL(%s, %s)", 'main_table.base_shipping_refunded', 0)),
                new Zend_Db_Expr(sprintf("IFNULL(%s, %s)", 'main_table.base_shipping_canceled', 0)),
            )
        );
        $this->getSelect()->columns(array(
            'orders_shipping_sum' => new Zend_Db_Expr(sprintf('SUM(%s)', $shippingExpr))
        ));
*/

/*
        $taxExpr = vsprintf(
            '(main_table.base_tax_amount - %s - %s) * main_table.base_to_global_rate',
            array(
                new Zend_Db_Expr(sprintf("IFNULL(%s, %s)", 'main_table.base_tax_refunded', 0)),
                new Zend_Db_Expr(sprintf("IFNULL(%s, %s)", 'main_table.base_tax_canceled', 0)),
            )
        );
        $this->getSelect()->columns(array(
            'orders_tax_sum' => new Zend_Db_Expr(sprintf('SUM(%s)', $taxExpr))
        ));
*/

        if ($groupByCustomers) {
            $this->_groupByCustomers($from, $to);
        }

        $this->addFieldToFilter('main_table.created_at', array(
            'from'  => $from,
            'to'    => $to
        ));

        $this->addSumAvgTotals();

        $this->addOrdersCount();

        $this->_addSumAvgItems();

        return $this;

    }

    protected function _addRevenueExpression()
    {
        // _getSalesAmountExpression for 'revenue'
        $revenueExpression = vsprintf(
            '(%s - %s - %s - (%s - %s - %s)) * main_table.base_to_global_rate',
            array(
                // instead of $adapter->getIfNullSql() which does not exist in old versions
                new Zend_Db_Expr(sprintf("IFNULL(%s, %s)", 'main_table.base_total_invoiced', 0)),
                new Zend_Db_Expr(sprintf("IFNULL(%s, %s)", 'main_table.base_tax_invoiced', 0)),
                new Zend_Db_Expr(sprintf("IFNULL(%s, %s)", 'main_table.base_shipping_invoiced', 0)),
                new Zend_Db_Expr(sprintf("IFNULL(%s, %s)", 'main_table.base_total_refunded', 0)),
                new Zend_Db_Expr(sprintf("IFNULL(%s, %s)", 'main_table.base_tax_refunded', 0)),
                new Zend_Db_Expr(sprintf("IFNULL(%s, %s)", 'main_table.base_shipping_refunded', 0)),
            )
        );
        $this->getSelect()
            ->columns(array(
                'revenue_sum' => new Zend_Db_Expr(sprintf('SUM(%s)', $revenueExpression)),
                'revenue_avg' => new Zend_Db_Expr(sprintf('AVG(%s)', $revenueExpression)),
            ))
            ->where('main_table.state NOT IN (?)', array(
                    Mage_Sales_Model_Order::STATE_PENDING_PAYMENT,
                    Mage_Sales_Model_Order::STATE_NEW)
            );

        return $this;
    }

    protected function _groupByCustomers($from, $to)
    {
        $adapter = $this->getConnection();

        $registeredSubExpression = new Zend_Db_Expr(
            sprintf("IF(%s AND %s, 'newcustomers', 'oldcustomers')",
                $adapter->quoteInto('customers.created_at >= ?', $from),
                $adapter->quoteInto('customers.created_at <= ?', $to)
            )
        );
        $registeredExpression = new Zend_Db_Expr("IF(main_table.customer_id IS NULL, 'guests', {$registeredSubExpression})");
        $this->getSelect()
            ->columns(array(
                // 0 - guest customers, 1 - registered within period, 2 - old customers
                'customer_type' => $registeredExpression,
            ))
            ->joinLeft(array('customers' => $this->getTable('customer/entity')),
                'customers.entity_id = main_table.customer_id',
                array()
            )
            ->group($registeredExpression)
        ;
        return $this;
    }

    protected function _addSumAvgItems()
    {
        $this->getSelect()
            ->columns(array(
                "items_qty_sum" => "SUM(main_table.total_qty_ordered)",
                "items_qty_avg" => "AVG(main_table.total_qty_ordered)"
            ));
        return $this;
    }
}