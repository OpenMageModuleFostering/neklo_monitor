<?php

class Neklo_Monitor_Model_Resource_Log_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    protected function _construct()
    {
        $this->_init('neklo_monitor/log');
    }

    public function getTotal($isClearQtyNew = false)
    {
        $total = $this->_prepareTotal()->getFirstItem()->getData();
        if ($isClearQtyNew) {
            $this->_clearQtyNew($this->getAllIds());
        }
        return $total;
    }

    protected function _prepareTotal()
    {
        $this->getSelect()->reset(Zend_Db_Select::COLUMNS);
        $this->getSelect()->columns(
            array(
                'qty'     => 'SUM(qty)',
                'qty_new' => 'SUM(qty_new)',
            )
        );
        return $this;
    }

    protected function _clearQtyNew($idList = array())
    {
        $idExpression = new Zend_Db_Expr('log_id in (' . join(',', $idList) . ')');
        $this->getConnection()->update(
            $this->getMainTable(), array('qty_new' => 0), $idExpression
        );
        return $this;
    }
}