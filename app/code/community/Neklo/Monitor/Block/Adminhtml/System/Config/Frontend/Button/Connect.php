<?php

class Neklo_Monitor_Block_Adminhtml_System_Config_Frontend_Button_Connect extends Mage_Adminhtml_Block_Template
{
    protected $_api = null;

    /**
     * @return Mage_Adminhtml_Block_Widget_Button
     */
    public function getButton()
    {
        $button = $this->getLayout()->createBlock('adminhtml/widget_button');
        $button
            ->setType('button')
            ->setLabel($this->__('Connect to Gateway'))
            ->setStyle("width:280px")
            ->setId('neklo_monitor_button')
        ;
        return $button;
    }

    /**
     * @return string
     */
    public function getButtonHtml()
    {
        return $this->getButton()->toHtml();
    }

    /**
     * @return string
     */
    public function getContainerId()
    {
        return parent::getContainerId();
    }

    public function getConnectUrl()
    {
        return $this->getUrl('adminhtml/neklo_monitor_gateway/connect');
    }

    /**
     * @return Neklo_Monitor_Helper_Config
     */
    protected function _getConfig()
    {
        return Mage::helper('neklo_monitor/config');
    }
}