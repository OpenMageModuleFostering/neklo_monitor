<?php

class Neklo_Monitor_Model_Log extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        $this->_init('neklo_monitor/log');
    }

    public function loadByHash($hash)
    {
        return $this->load($hash, 'hash');
    }

    // TODO: check
    protected function _afterLoad()
    {
        $list = explode(',', $this->_getData('times'));

        // sort by time DESC
        $list = array_unique($list, SORT_NUMERIC);
        sort($list, SORT_NUMERIC);
        $list = array_reverse($list);

        $this->_data['times_list'] = $list;
        return parent::_afterLoad();
    }

    // TODO: check
    /**
     * @return Varien_Data_Collection
     */
    public function getTimesCollection($startFrom = 0, $limit = null, $filter = array())
    {
        // apply filters

        $list = $this->_getData('times_list');
        if ($filter) {
            foreach ($list as $_key => $_data) {
                $valid = true;
                foreach ($filter as $_expr => $_value) {
                    if      ('lt' == $_expr)   { if ($_data >= $_value) $valid = false; }
//                        else if ('lteq' == $_expr) { if ($_data[$_field] > $_value)  $valid = false; }
//                        else if ('gt' == $_expr)   { if ($_data[$_field] <= $_value) $valid = false; }
                    else if ('gteq' == $_expr) { if ($_data < $_value)  $valid = false; }
//                        else if ('eq' == $_expr)   { if ($_data[$_field] <> $_value) $valid = false; }
//                        else if ('neq' == $_expr)  { if ($_data[$_field] == $_value) $valid = false; }
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
}