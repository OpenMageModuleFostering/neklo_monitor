<?php


class Neklo_Monitor_Model_Resource_Minfo_Report extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init('neklo_monitor/report', 'report_id');
    }

    public function fetchMaxMtime()
    {
        $select = $this->_getReadAdapter()->select()
            ->from($this->getMainTable(), array('max_mtime' => new Zend_Db_Expr('MAX(`last_time`)')));
        return (int) $this->_getReadAdapter()->fetchOne($select);
    }

    public function saveReports($reports)
    {
        $conn = $this->_getWriteAdapter();
        $updated = $inserted = 0;
        foreach ($reports as $hash => $data) {

            $select = $conn->select()
                ->from($this->getMainTable())
                ->where('hash = ?', $hash);
            $row = $conn->fetchRow($select);

            $dbData = array(
                'last_time' => $data['last_time'],
                'qty' => $data['qty'],
                'files' => Mage::helper('core')->jsonEncode($data['files']),
            );
            if ($row) {
                $conn->update($this->getMainTable(), $dbData, array('report_id = ?' => $row['report_id']));
                $updated++;
            } else {
                $dbData['hash'] = $hash;
                $dbData['message'] = $data['message'];
                $dbData['first_time'] = $data['first_time'];
                $conn->insert($this->getMainTable(), $dbData);
                $inserted++;
            }
        }

        return array($inserted, $updated);
    }

}