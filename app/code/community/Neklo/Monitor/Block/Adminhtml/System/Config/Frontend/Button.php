<?php

class Neklo_Monitor_Block_Adminhtml_System_Config_Frontend_Button extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $element->setScope(false);
        $element->setCanUseWebsiteValue(false);
        $element->setCanUseDefaultValue(false);
        return parent::render($element);
    }

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        if (!$this->_getConfig()->isConnected()) {
            $button = $this->getLayout()->createBlock('neklo_monitor_adminhtml/system_config_frontend_button_connect', 'neklo_monitor_button');
            $button->setTemplate('neklo/monitor/system/config/button/connect.phtml');
        } else {
            $button = $this->getLayout()->createBlock('neklo_monitor_adminhtml/system_config_frontend_button_disconnect', 'neklo_monitor_button');
            $button->setTemplate('neklo/monitor/system/config/button/disconnect.phtml');
        }
        $button->setContainerId($element->getContainer()->getHtmlId());
        return $button->toHtml();
    }

    /**
     * @return Neklo_Monitor_Helper_Config
     */
    protected function _getConfig()
    {
        return Mage::helper('neklo_monitor/config');
    }
}