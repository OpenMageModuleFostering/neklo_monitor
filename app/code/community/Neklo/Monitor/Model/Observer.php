<?php

class Neklo_Monitor_Model_Observer
{
    /**
     * @param Varien_Event_Observer $observer
     * @observe order place event
     */
    public function checkOrder(Varien_Event_Observer $observer)
    {
        if (!$this->_getConfig()->isEnabled()) {
            return;
        }

        if (!$this->_getConfig()->isConnected()) {
            return;
        }

        /** @var Mage_Sales_Model_Order $order */
        $order = $observer->getEvent()->getOrder();

        $total = $order->getGrandTotal();
        $totalFormated = $order->getOrderCurrency()->format($total, array(), false);

        $info = array(
            'increment_id'          => $order->getIncrementId(),
            'grand_total'           => $total,
            'grand_total_formated'  => $totalFormated,
            'qty'                   => $order->getTotalQtyOrdered(),
        );

        $this->_addToRequestQueue('order', $info);
    }

    protected function _addToRequestQueue($type, $info)
    {
        /** @var Neklo_Monitor_Model_Gateway_Queue $queue */
        $queue = Mage::getModel('neklo_monitor/gateway_queue');
        $queue
            ->setType($type)
            ->setMessage(Mage::helper('core')->jsonEncode($info))
            ->setScheduledAt(time())
            ->save();
    }

    // * * * * * cronjob

    public function sendQueuedRequest(Mage_Cron_Model_Schedule $schedule)
    {
        if (!$this->_getConfig()->isEnabled()) {
            $schedule->setMessages('Disabled');
            return;
        }

        if (!$this->_getConfig()->isConnected()) {
            $schedule->setMessages('Not connected');
            return;
        }

        $startedAt = time();

        /** @var Neklo_Monitor_Model_Resource_Gateway_Queue $resc */
        $resc = Mage::getResourceModel('neklo_monitor/gateway_queue');

        // release entries stuck for 1hr and more: started, but not sent

        $countStuck = $resc->releaseEntries($startedAt - 60 * 60);
        if ($countStuck > 0) {
            $schedule->setMessages($schedule->getMessages()
                . sprintf('%d stuck items rescheduled.', $countStuck));
        }


        // remove old entries sent 30 days ago: started and sent

        $countOld = $resc->cleanupEntries($startedAt - 60 * 60 * 24 * 30);
        if ($countOld > 0) {
            $schedule->setMessages($schedule->getMessages()
                . sprintf('%d archive items removed.', $countOld));
        }


        /** @var Neklo_Monitor_Model_Resource_Gateway_Queue_Collection $collSending */
        $collSending = Mage::getResourceModel('neklo_monitor/gateway_queue_collection');
        $collSending->addFieldToFilter('started_at', $startedAt);
        if ($collSending->getSize() > 0) {
            // to prevent several cron runs at the same timestamp
            return;
        }

        // mark pending requests to run at $time
        // to prevent same rows sent by different cron processes,
        // i.e. when previous sending process lasts too long and another cron process has started

        $resc->bookEntries($startedAt);

        // fetch entries to send

        /** @var Neklo_Monitor_Model_Resource_Gateway_Queue_Collection $collToSend */
        $collToSend = Mage::getResourceModel('neklo_monitor/gateway_queue_collection');
        $collToSend->addFieldToFilter('started_at', $startedAt);
        if (!$collToSend->getSize()) {
            $schedule->setMessages($schedule->getMessages()
                . 'Nothing to send');
            return;
        }

        $requestData = array();
        foreach ($collToSend as $queue) {
            /** @var Neklo_Monitor_Model_Gateway_Queue $queue */
            if (!array_key_exists($queue->getType(), $requestData)) {
                $requestData[ $queue->getType() ] = array();
            }
            $requestData[ $queue->getType() ][] = base64_encode($queue->getMessage());
        }

        try {
            $gatewayConfig = $this->_getConnector()->sendInfo(null, $requestData, 'alert');
            $this->_getConfig()->updateGatewayConfig($gatewayConfig);
        } catch (Exception $e) {
            $schedule->setMessages($schedule->getMessages()
                . $e->getMessage());
//            Mage::logException($e);
        }

        if ($collToSend->count() > 0) {
            $schedule->setMessages($schedule->getMessages()
                . sprintf('%d items sent.', $collToSend->count())); // collection is already loaded, so we do not use ->getSize()
        }

        // mark as sent

        $sentAt = time();
        $resc->sentEntries($startedAt, $sentAt);

    }

    // * * * * * cronjob
    // create queue messages based on inventory changelog product ids list
    public function convertChangelogToQueue(Mage_Cron_Model_Schedule $schedule)
    {
        if (!$this->_getConfig()->isEnabled()) {
            $schedule->setMessages('Disabled');
            return;
        }

        if (!$this->_getConfig()->isConnected()) {
            $schedule->setMessages('Not connected');
            return;
        }

        /** @var Neklo_Monitor_Model_Resource_Changelog $resrc */
        $resrc = Mage::getResourceModel('neklo_monitor/changelog');
        $data = $resrc->fetchChangelog();
        if ($data && is_array($data)) {
            foreach ($data as $_item) {
                $this->_addToRequestQueue('inventory', array(
                    'name'              => $_item['name'],
                    'sku'               => $_item['sku'],
                    'attribute_set_id'  => $_item['attribute_set_id'],
                    'attribute_set_name'=> $_item['attribute_set_name'],
                    'qty'               => $_item['qty'],
                    'in_stock'          => $_item['stock_status'] ? 1 : 0,
                ));
            }

            $schedule->setMessages($schedule->getMessages()
                . sprintf('Ready to send %d inventory updates.', count($data)));
        }
    }

    public function aggregateSalesReportOrderData($schedule)
    {
        $report = Mage::getResourceModel('neklo_monitor/minfo_daily')->collect();

        $this->_addToRequestQueue('dailyreport', $report);

        $schedule->setMessages($schedule->getMessages()
            . sprintf('Collected sales report with %d new orders.', $report['orders']['all']['orders_count']));
    }

    /**
     * @return Neklo_Monitor_Helper_Config
     */
    protected function _getConfig()
    {
        return Mage::helper('neklo_monitor/config');
    }

    /**
     * @return Neklo_Monitor_Model_Gateway_Connector
     */
    protected function _getConnector()
    {
        return Mage::getSingleton('neklo_monitor/gateway_connector');
    }
}
