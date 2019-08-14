<?php

class Neklo_Monitor_Block_Adminhtml_System_Config_Frontend_Label extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        return '<p id="'. $element->getHtmlId() . '">' . parent::_getElementHtml($element) .'</p>';
    }
}
