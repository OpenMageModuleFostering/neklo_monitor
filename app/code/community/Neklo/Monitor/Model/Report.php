<?php

/**
 * @method Neklo_Monitor_Model_Resource_Report getResource()
 */
class Neklo_Monitor_Model_Report extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        $this->_init('neklo_monitor/report');
    }

    public function saveReports($reportList)
    {
        return $this->getResource()->saveReports($reportList);
    }

    // TODO: clean
    /**
     * @return Varien_Data_Collection
     */
    public function getFilesCollection($startFrom = 0, $limit = null, $filter = array())
    {
        // apply filters

        $list = $this->_getData('files_list');
        if ($filter) {
            foreach ($list as $_key => $_data) {
                $valid = true;
                foreach ($filter as $_field => $_cond) {
                    if (!isset($_data[$_field])) {
                        $_data[$_field] = 0; // temporary, will not affect $list
                    }
                    foreach ($_cond as $_expr => $_value) {
                        if      ('lt' == $_expr)   { if ($_data[$_field] >= $_value) $valid = false; }
//                        else if ('lteq' == $_expr) { if ($_data[$_field] > $_value)  $valid = false; }
//                        else if ('gt' == $_expr)   { if ($_data[$_field] <= $_value) $valid = false; }
                        else if ('gteq' == $_expr) { if ($_data[$_field] < $_value)  $valid = false; }
//                        else if ('eq' == $_expr)   { if ($_data[$_field] <> $_value) $valid = false; }
//                        else if ('neq' == $_expr)  { if ($_data[$_field] == $_value) $valid = false; }
                    }
                }
                if (!$valid) {
                    unset($list[$_key]);
                }
            }
        }

        // apply limits

        $lastIdx = count($list) - 1;
        if (is_null($limit)) {
            $finishAt = $lastIdx;
        } else {
            $finishAt = $startFrom + $limit - 1;
            if ($finishAt > $lastIdx) {
                $finishAt = $lastIdx;
            }
        }

        $collection = new Varien_Data_Collection();
        $k = $startFrom;
        $list = array_values($list); // avoid assoc array, convert to numeric array keys
        while ($k <= $finishAt) {
            $_file = new Varien_Object($list[$k]);
            $collection->addItem($_file);
            $k++;
        }

        return $collection;
    }

    // TODO: clean
    protected function _afterLoad()
    {
        $files = Mage::helper('core')->jsonDecode($this->_getData('files'));

        $list = array();
        foreach ($files as $_data) {
            $key = $_data['time'] . '_' . $_data['name'];
//            $_data['key'] = $key;
            $list[$key] = $_data;
        }

        // sort by mtime DESC

        ksort($list);
        $list = array_reverse($list, true);

        $this->_data['files_list'] = $list;
        return parent::_afterLoad();
    }
}