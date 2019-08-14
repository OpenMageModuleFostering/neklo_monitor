<?php


class Neklo_Monitor_Model_Resource_Gateway_Queue extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init('neklo_monitor/queue', 'queue_id');
    }

    public function bookEntries($startedAt)
    {
        return $this->_getWriteAdapter()->update($this->getMainTable(),
            array('started_at' => $startedAt),
            array('started_at = ?' => 0)
        );
    }

    public function sentEntries($startedAt, $sentAt)
    {
        return $this->_getWriteAdapter()->update($this->getMainTable(),
            array('sent_at' => $sentAt),
            array('started_at = ?' => $startedAt)
        );
    }

    public function releaseEntries($startedAt)
    {
        return $this->_getWriteAdapter()->update($this->getMainTable(),
            array('started_at' => 0),
            array(
                'started_at < ?' => $startedAt,
                'sent_at = ?' => 0,
            )
        );
    }

    public function cleanupEntries($startedAt)
    {
        return $this->_getWriteAdapter()->delete($this->getMainTable(),
            array(
                'started_at > ?' => 0,
                'started_at < ?' => $startedAt,
                'sent_at > ?' => 0,
                'sent_at < ?' => $startedAt,
            )
        );
    }
}