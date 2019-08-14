<?php

class Neklo_Monitor_Model_Log_Writer_Stream extends Zend_Log_Writer_Stream
{
    protected $_isSystemFile = false;

    public function __construct($streamOrUrl, $mode = 'a')
    {
        if ($this->_getConfig()->isEnabled() && !is_resource($streamOrUrl)) {
            $filename = basename($streamOrUrl);
            if (
                Mage::getStoreConfig('dev/log/file') == $filename
                || Mage::getStoreConfig('dev/log/exception_file') == $filename
            ) {
                $this->_isSystemFile = true;
            }
        }
        parent::__construct($streamOrUrl, $mode);
    }

    protected function _write($event)
    {
        if ($this->_getConfig()->isEnabled() && $this->_canSave()) {
            $this->_saveLog($event);
        }
        parent::_write($event);
    }

    protected function _canSave()
    {
        return $this->_isSystemFile;
    }

    protected function _saveLog($event)
    {
        $hash = md5(trim($event['message']));

        /* @var $log Neklo_Monitor_Model_Log */
        $log = Mage::getModel('neklo_monitor/log');
        $log->loadByHash($hash);

        if ($log->getId()) {
            $log->addData(
                array(
                    'last_time' => $this->_getTimestamp($event['timestamp']),
                    'qty'       => $log->getQty() + 1,
                    'qty_new'   => $log->getQtyNew() + 1,
                )
            );
        } else {
            $log->addData(
                array(
                    'first_time' => $this->_getTimestamp($event['timestamp']),
                    'last_time'  => $this->_getTimestamp($event['timestamp']),
                    'qty'        => 1,
                    'qty_new'    => 1,
                    'message'    => $event['message'],
                    'hash'       => $hash,
                )
            );
        }

        $log->save();
    }

    protected function _getTimestamp($date)
    {
        $zDate = new Zend_Date($date, Zend_Date::ISO_8601);
        return $zDate->getTimestamp();
    }

    /**
     * @return Neklo_Monitor_Helper_Config
     */
    protected function _getConfig()
    {
        return Mage::helper('neklo_monitor/config');
    }
}