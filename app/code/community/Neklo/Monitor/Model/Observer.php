<?php

class Neklo_Monitor_Model_Observer
{
    public function actionPreDispatchConfigPage(Varien_Event_Observer $observer)
    {
        $request = $observer->getControllerAction()->getRequest();
        $currentSection = $request->getParam('section', null);
        if ($currentSection === 'neklo_monitor' && Mage::helper('neklo_monitor/config')->isUpdateAvailable()) {
            Mage::getSingleton('adminhtml/session')->addNotice(
                Mage::helper('neklo_monitor')->__(
                    'Currently you are using Magento Store Monitoring <b>%s</b>. Version <b>%s</b> is available. <a href="%s" target="_blank">Update now</a>.',
                    Mage::helper('neklo_monitor/config')->getModuleVersion(),
                    Mage::helper('neklo_monitor/config')->getLastVersion(),
                    Mage::helper('neklo_monitor/config')->getExtensionUrl()
                )
            );
        }
    }

    /**
     * @param Varien_Event_Observer $observer
     * @observe order place event
     */
    public function orderPlaceAfter(Varien_Event_Observer $observer)
    {
        if (!$this->_getConfig()->isEnabled()) {
            return;
        }

        if (!$this->_getConfig()->isConnected()) {
            return;
        }

        /* @var Mage_Sales_Model_Order $order */
        $order = $observer->getEvent()->getOrder();

        // TODO: add base grand total
        $total = $order->getGrandTotal();
        $totalFormatted = $order->getOrderCurrency()->format($total, array(), false);

        $info = array(
            'increment_id'          => $order->getIncrementId(),
            'grand_total'           => $total,
            // TODO: typo grand_total_formatted
            'grand_total_formated'  => $totalFormatted,
            'qty'                   => $order->getTotalQtyOrdered(),
        );

        $this->_addToRequestQueue(Neklo_Monitor_Model_Source_Gateway_Queue_Type::ORDER_CODE, $info);
    }

    protected function _addToRequestQueue($type, $info)
    {
        /* @var Neklo_Monitor_Model_Gateway_Queue $queue */
        $queue = Mage::getModel('neklo_monitor/gateway_queue');
        $queue->addToQueue($type, $info);
    }

    /**
     * @return Neklo_Monitor_Helper_Config
     */
    protected function _getConfig()
    {
        return Mage::helper('neklo_monitor/config');
    }
}
