<?php

class Neklo_Monitor_Block_Adminhtml_System_Config_Frontend_Status extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $element->setBold(true);

        // neklo_monitor_gateway_status_sandbox || neklo_monitor_gateway_status_production
        $name = explode('_', $element->getId());
        $serverType = array_pop($name); // sandbox || production

        if ($this->_getConfig()->isConnected($serverType)) {
            $element->setValue($this->__('Connected to the %s Gateway', $serverType));
            $element->addClass('gateway_status')->addClass('success');
        } else {
            $element->setValue($this->__('Not connected to the %s Gateway', $serverType));
            $element->addClass('gateway_status')->addClass('error');
        }
        return '<p id="'. $element->getHtmlId() . '" ' . $element->serialize($element->getHtmlAttributes()) . '>' . parent::_getElementHtml($element) .'</p>';
    }

    protected function _prepareLayout()
    {
        /* @var $head Mage_Page_Block_Html_Head */
        $head = $this->getLayout()->getBlock('head');
        if ($head) {
            $head->addItem('skin_css', 'neklo/monitor/css/styles.css');
        }
        return parent::_prepareLayout();
    }

    /**
     * @return Neklo_Monitor_Helper_Config
     */
    protected function _getConfig()
    {
        return Mage::helper('neklo_monitor/config');
    }
}
