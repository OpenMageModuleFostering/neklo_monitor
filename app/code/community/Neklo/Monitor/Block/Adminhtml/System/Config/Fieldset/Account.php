<?php

class Neklo_Monitor_Block_Adminhtml_System_Config_Fieldset_Account extends Mage_Adminhtml_Block_System_Config_Form_Fieldset
{
    protected function _getHeaderHtml($element)
    {
        return parent::_getHeaderHtml($element) . $this->_getContentHtml();
    }

    protected function _getContentHtml()
    {
        $accountListBlock = $this->getLayout()->createBlock(
            'neklo_monitor_adminhtml/system_config_fieldset_account_list',
            'neklo_monitor_account_list'
        );
        return $accountListBlock->toHtml();
    }
}