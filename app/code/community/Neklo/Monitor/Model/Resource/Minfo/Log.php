<?php


class Neklo_Monitor_Model_Resource_Minfo_Log extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init('neklo_monitor/log', 'log_id');
    }

    public function fetchMaxtime($type)
    {
        $select = $this->_getReadAdapter()->select()
            ->from($this->getMainTable(), array('max_time' => new Zend_Db_Expr('MAX(`last_time`)')))
            ->where('type = ?', $type);
        return (int) $this->_getReadAdapter()->fetchOne($select);
    }

    public function saveLogs($type, $logs)
    {
        $conn = $this->_getWriteAdapter();
        $updated = $inserted = 0;
        foreach ($logs as $hash => $data) {

            $select = $conn->select()
                ->from($this->getMainTable())
                ->where('hash = ?', $hash)
                ->where('type = ?', $type);
            $row = $conn->fetchRow($select);

            $data['times'] = array_unique($data['times'], SORT_NUMERIC);
            sort($data['times'], SORT_NUMERIC);
            $data['times'] = array_reverse($data['times']);

            $dbData = array(
                'last_time' => $data['last_time'],
                'qty' => $data['qty'],
                'times' => implode(',', $data['times']),
            );
            if ($row) {
                $conn->update($this->getMainTable(), $dbData, array('log_id = ?' => $row['log_id']));
                $updated++;
            } else {
                $dbData['first_time'] = $data['first_time'];
                $dbData['hash'] = $hash;
                $dbData['type'] = $type;
                $dbData['message'] = $data['message'];
                $conn->insert($this->getMainTable(), $dbData);
                $inserted++;
            }
        }

        return array($inserted, $updated);
    }
}
