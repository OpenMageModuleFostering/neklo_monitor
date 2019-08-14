<?php

class Neklo_Monitor_Block_Adminhtml_System_Config_Fieldset_Account_List extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    public function __construct()
    {
        $this->setTemplate('neklo/monitor/system/config/fieldset/account/list.phtml');
        parent::__construct();
    }

    public function getAccountRows()
    {
        $collection = Mage::getResourceModel('neklo_monitor/account_collection');
        foreach ($collection as $account) {
            $account->setData('_id', $account->getId());
        }
        return $collection;
    }

    public function getElement()
    {
        return new Varien_Object();
    }

    protected function _prepareToRender()
    {
        $this->addColumn(
            'phone_mask',
            array(
                'label' => $this->__('Phone'),
                'style' => 'width:150px',
                'class' => 'required-entry validate-neklo-monitor-phone',
            )
        );
        $this->addColumn(
            'firstname',
            array(
                'label' => $this->__('Firstname'),
                'style' => 'width:150px',
                'class' => 'required-entry',
            )
        );
        $this->addColumn(
            'lastname',
            array(
                'label' => $this->__('Lastname'),
                'style' => 'width:150px',
                'class' => 'required-entry',
            )
        );
        $this->addColumn(
            'email',
            array(
                'label' => $this->__('Email'),
                'style' => 'width:150px',
                'class' => 'required-entry validate-email',
            )
        );
        $this->_addButtonLabel = $this->__('Add');

        parent::_prepareToRender();
    }

    public function getSaveUrl()
    {
        return $this->getUrl('adminhtml/neklo_monitor_gateway_account/add');
    }

    public function getRemoveUrl()
    {
        return $this->getUrl('adminhtml/neklo_monitor_gateway_account/remove');
    }
}