<?php

class Neklo_Monitor_Model_Cron_Queue extends Neklo_Monitor_Model_Cron_Abstract
{
    public function convertInventoryChangelogToQueue(Mage_Cron_Model_Schedule $schedule)
    {
        if (!$this->_getConfig()->isEnabled()) {
            $schedule->setMessages('Neklo_Monitor is disabled');
            return;
        }

        if (!$this->_getConfig()->isConnected()) {
            $schedule->setMessages('Neklo_Monitor is not connected to gateway');
            return;
        }

        /* @var Neklo_Monitor_Model_Resource_Changelog $changelog */
        $changelog = Mage::getResourceModel('neklo_monitor/changelog');
        $changelogData = $changelog->fetch();
        if ($changelogData && is_array($changelogData)) {
            foreach ($changelogData as $_item) {
                $this->_addToRequestQueue(
                    Neklo_Monitor_Model_Source_Gateway_Queue_Type::INVENTORY_CODE,
                    array(
                        'name'               => $_item['name'],
                        'sku'                => $_item['sku'],
                        'attribute_set_id'   => $_item['attribute_set_id'],
                        'attribute_set_name' => $_item['attribute_set_name'],
                        'qty'                => $_item['qty'],
                        'in_stock'           => $_item['stock_status'] ? 1 : 0,
                    )
                );
            }

            $schedule->setMessages(
                sprintf('Ready to send %d inventory updates.', count($changelogData))
            );
        }
    }

    public function aggregateSalesReportOrderData($schedule)
    {
        if (!$this->_getConfig()->isEnabled()) {
            $schedule->setMessages('Neklo_Monitor is disabled');
            return;
        }

        if (!$this->_getConfig()->isConnected()) {
            $schedule->setMessages('Neklo_Monitor is not connected to gateway');
            return;
        }

        $report = Mage::getResourceModel('neklo_monitor/minfo_daily')->collect();
        $this->_addToRequestQueue(Neklo_Monitor_Model_Source_Gateway_Queue_Type::DAILY_REPORT_CODE, $report);
        $schedule->setMessages(
            sprintf('Collected sales report with %d new orders.', $report['orders']['all']['orders_count'])
        );
    }

    public function sendQueue(Mage_Cron_Model_Schedule $schedule)
    {
        if (!$this->_getConfig()->isEnabled()) {
            $schedule->setMessages('Neklo_Monitor is disabled');
            return;
        }

        if (!$this->_getConfig()->isConnected()) {
            $schedule->setMessages('Neklo_Monitor is not connected to gateway');
            return;
        }

        $startedAt = time();

        /* @var Neklo_Monitor_Model_Resource_Gateway_Queue $gatewayQueue */
        $gatewayQueue = Mage::getResourceModel('neklo_monitor/gateway_queue');

        // release entries stuck for 1hr and more: started, but not sent
        $countStuck = $gatewayQueue->releaseEntries($startedAt - 60 * 60);
        if ($countStuck > 0) {
            $schedule->setMessages(
                $schedule->getMessages() . sprintf('%d stuck items rescheduled.', $countStuck)
            );
        }

        // remove old entries sent 30 days ago: started and sent
        $countOld = $gatewayQueue->cleanupEntries(
            $startedAt - 60 * 60 * 24 * 30
        );
        if ($countOld > 0) {
            $schedule->setMessages(
                $schedule->getMessages() . sprintf('%d archive items removed.', $countOld)
            );
        }

        /* @var Neklo_Monitor_Model_Resource_Gateway_Queue_Collection $queueCollection */
        $queueCollection = Mage::getResourceModel('neklo_monitor/gateway_queue_collection');
        $queueCollection->addFieldToFilter('started_at', $startedAt);
        if ($queueCollection->getSize() > 0) {
            // to prevent several cron runs at the same timestamp
            return;
        }

        // mark pending requests to run at $time
        // to prevent same rows sent by different cron processes,
        // i.e. when previous sending process lasts too long and another cron process has started
        $gatewayQueue->bookEntries($startedAt);

        // fetch entries to send
        /* @var Neklo_Monitor_Model_Resource_Gateway_Queue_Collection $queueCollection */
        $queueCollection = Mage::getResourceModel('neklo_monitor/gateway_queue_collection');
        $queueCollection->addFieldToFilter('started_at', $startedAt);
        if (!$queueCollection->getSize()) {
            $schedule->setMessages('Nothing to send');
            return;
        }

        $requestData = array();
        foreach ($queueCollection as $queue) {
            /* @var Neklo_Monitor_Model_Gateway_Queue $queue */
            if (!array_key_exists($queue->getType(), $requestData)) {
                $requestData[$queue->getType()] = array();
            }
            // TODO: check base64
            $requestData[$queue->getType()][] = base64_encode($queue->getMessage());
        }

        try {
            $gatewayConfig = $this->_getConnector()->sendAlert($requestData);
            $this->_getConfig()->updateGatewayConfig($gatewayConfig);

            if ($queueCollection->count() > 0) {
                $schedule->setMessages(
                    sprintf('%d items sent.', $queueCollection->count())
                );
            }

            // mark as sent
            $sentAt = time();
            $gatewayQueue->sentEntries($startedAt, $sentAt);
        } catch (Exception $e) {
            $schedule->setMessages($e->getMessage());
            Mage::logException($e);
        }
    }

    protected function _addToRequestQueue($type, $info)
    {
        /* @var Neklo_Monitor_Model_Gateway_Queue $queue */
        $queue = Mage::getModel('neklo_monitor/gateway_queue');
        $queue->addToQueue($type, $info);
    }
}