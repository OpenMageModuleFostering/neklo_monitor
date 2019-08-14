<?php

class Neklo_Monitor_Block_Adminhtml_System_Config_Frontend_Button_Disconnect extends Mage_Adminhtml_Block_Template
{
    /**
     * @return Mage_Adminhtml_Block_Widget_Button
     */
    public function getButton()
    {
        $button = $this->getLayout()->createBlock('adminhtml/widget_button');
        $button
            ->setType('button')
            ->setLabel($this->__('Disconnect'))
            ->setStyle("width:280px")
            ->setId('neklo_monitor_button')
            ->setClass('delete')
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

    public function getDisconnectUrl()
    {
        return Mage::helper("adminhtml")->getUrl("adminhtml/neklo_monitor_gateway/disconnect");
    }
}