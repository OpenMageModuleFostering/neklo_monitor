<?php

class Neklo_Monitor_Model_Resource_Report extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init('neklo_monitor/report', 'report_id');
    }

    public function saveReports($reports)
    {
        if (!count($reports)) {
            return 0;
        }
        $updated = $inserted = 0;

        $writeAdapter = $this->_getWriteAdapter();

        // Update exists rows
        $hashList = array_map(
            array($writeAdapter, 'quote'), array_keys($reports)
        );
        $hashExpression = new Zend_Db_Expr(join(',', $hashList));
        $select = $writeAdapter->select()
            ->from($this->getMainTable())
            ->where('hash in (?)', $hashExpression)
        ;

        $rowList = $writeAdapter->fetchAll($select);
        foreach ($rowList as $row) {
            if (!array_key_exists($row['hash'], $reports)) {
                continue;
            }
            $reportData = $reports[$row['hash']];
            unset($reports[$row['hash']]);

            $updateData = array(
                'last_time' => $reportData['last_time'],
                'qty'       => $row['qty'] + $reportData['qty'],
                'qty_new'   => $row['qty_new'] + $reportData['qty'],
                'files'     => Mage::helper('core')->jsonEncode($reportData['files']),
            );
            $writeAdapter->update(
                $this->getMainTable(),
                $updateData,
                array('report_id = ?' => $row['report_id'])
            );
            $updated++;
        }

        // Insert new rows
        foreach ($reports as $hash => $reportData) {
            $reportData['qty_new'] = $reportData['qty'];
            $reportData['files'] = Mage::helper('core')->jsonEncode($reportData['files']);
            $writeAdapter->insert($this->getMainTable(), $reportData);
            $inserted++;
        }

        // TODO: check
        return array($inserted, $updated);
    }
}