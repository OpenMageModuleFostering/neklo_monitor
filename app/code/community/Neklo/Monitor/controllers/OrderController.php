<?php

class Neklo_Monitor_OrderController extends Neklo_Monitor_Controller_Abstract
{
    public function listAction()
    {
        /* @var $collection Mage_Sales_Model_Mysql4_Order_Grid_Collection */
        $collection = Mage::getResourceModel('sales/order_grid_collection');

        /** @var Neklo_Monitor_Helper_Date $hlpDate */
        $hlpDate = Mage::helper('neklo_monitor/date');

        // for pages lists - load next page rows despite newly inserted rows
        $queryTimestamp = (int) $this->_getRequestHelper()->getParam('query_timestamp', 0);
        $queryDate = $hlpDate->convertToString($queryTimestamp);
        if ($queryTimestamp > 0) {
            $collection->addFieldToFilter('main_table.created_at', array('lt' => $queryDate));
        }

        $offset = $this->_getRequestHelper()->getParam('offset', 0);
        $page = ceil($offset / self::PAGE_SIZE) + 1;
        $collection->setPage($page, self::PAGE_SIZE);

        $collection->setOrder('main_table.created_at', 'desc');

        $collection->getSelect()
            ->join(
                array('ce' => $collection->getTable('customer/entity')),
                'main_table.customer_id = ce.entity_id',
                array(
                    'email'             => 'ce.email',
                    'customer_group_id' => 'ce.group_id',
                )
            )
        ;

        if ($customerId = $this->_getRequestHelper()->getParam('customer_id', null)) {
            $collection->addFieldToFilter('customer_id', $customerId);
        }

        $storeId = $this->_getRequestHelper()->getParam('store', null);
        $store = Mage::app()->getStore($storeId);
        if ($storeId && $store->getId()) {
            $collection->addFieldToFilter('main_table.store_id', $store->getId());
        }

        $orderItemsSelect = $collection->getConnection()->select();
        $orderItemsSelect
            ->from(
                $collection->getTable('sales/order_item'),
                array(
                    'order_id'    => 'order_id',
                    'items_count' => 'count(item_id)',
                )
            )
            ->group('order_id')
        ;

        $collection->getSelect()
            ->join(
                array('oi' => $orderItemsSelect),
                'main_table.entity_id = oi.order_id',
                array(
                    'items_count' => 'oi.items_count',
                )
            )
        ;

        $orderStatusList = Mage::getSingleton('sales/order_config')->getStatuses();

        $groupList = Mage::getResourceModel('customer/group_collection')
            ->addFieldToFilter('customer_group_id', array('gt' => 0))
            ->load()
            ->toOptionHash()
        ;

        $orderList = array(
            'result' => array(),
        );
        foreach ($collection as $order) {
            /** @var $order Mage_Sales_Model_Order */
            if ((array_key_exists($order->getStatus(), $orderStatusList))) {
                $orderStatus = $orderStatusList[$order->getStatus()];
            } else {
                $orderStatus = 'N/A';
            }

            $orderData = array(
                'id'             => $order->getId(),
                'increment_id'   => $order->getIncrementId(),
                'created_at'     => $hlpDate->convertToTimestamp($order->getCreatedAt()),
                'status'         => $orderStatus,
                'grand_total'    => Mage::app()->getStore($order->getStoreId())->convertPrice($order->getBaseGrandTotal(), true, false),
                'items_count'    => (int)$order->getItemsCount(),
            );

            if ((array_key_exists($order->getCustomerGroupId(), $groupList))) {
                $customerGroup = $groupList[$order->getCustomerGroupId()];
            } else {
                $customerGroup = 'N/A';
            }

            $customerData = array(
                'id'    => $order->getCustomerId(),
                'email' => $order->getEmail(),
                'name'  => $order->getBillingName(),
                'group' => $customerGroup,
            );
            $orderData['customer'] = $customerData;

            $orderList['result'][] = $orderData;
        }

        // get new entities count

        if ($queryTimestamp > 0) {
            /* @var $collection Mage_Sales_Model_Mysql4_Order_Grid_Collection */
            $collection = Mage::getResourceModel('sales/order_grid_collection');
            $collection->addFieldToFilter('main_table.created_at', array('gteq' => $queryDate));
            $orderList['new_entities_count'] = $collection->getSize();
//            $orderList['sql'] = $collection->getSelectCountSql()->__toString();
        }

        $this->_jsonResult($orderList);
    }
}
