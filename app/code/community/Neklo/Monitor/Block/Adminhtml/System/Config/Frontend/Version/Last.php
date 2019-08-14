<?php

class Neklo_Monitor_Block_Adminhtml_System_Config_Frontend_Version_Last extends Neklo_Monitor_Block_Adminhtml_System_Config_Frontend_Label
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        return '<p id="'. $element->getHtmlId() . '">' . Mage::helper('neklo_monitor/config')->getLastVersion() .'</p>';
    }
}