<?php

/**
 * @method Neklo_Monitor_Model_Resource_Minfo_Log getResource()
 */
class Neklo_Monitor_Model_Minfo_Log extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        $this->_init('neklo_monitor/minfo_log');
    }

    public function generateLogs($type)
    {
        $logActive = Mage::getStoreConfig('dev/log/active');
        if (!Mage::getIsDeveloperMode() && !$logActive) {
            return false;
        }

        $file = Mage::getStoreConfig('dev/log/file');
        if ('system' != $type) {
            $type = 'exception';
            $file = Mage::getStoreConfig('dev/log/exception_file');
        }
        $logFile = Mage::getBaseDir('var') . DS . 'log' . DS . $file;
        if (!file_exists($logFile)) {
            return false;
        }

        $logs = array();

        $fh = fopen($logFile, "r");
        if (!$fh) {
            return false;
        }

        $maxTime = $this->getResource()->fetchMaxTime($type);

        // read log lines according to format
        // '%timestamp% %priorityName% (%priority%): %message%' . PHP_EOL;
        // see Mage::log()
        // exception's $message starts with "/n", i.e. can be found on next line

        while (($line = fgets($fh)) !== false) {
            // line should contain datetime, priorityName, $priority value least
            // e.g. "2016-04-18T13:39:42+00:00 ERR (3):"
            // yes, minimal priorityName is ERR, see Zend_Log
            if (strlen($line) < 34) {
                continue;
            }

            // skip exception stack traces
            if ('exception' == $type && 0 === strpos($line, '#')) {
                continue;
            }

            // search for line with datetime at the beginning
            // timestamp format is YYYY-mm-ddTHH:ii:ss+00:00
            $offsetTimezone = strpos($line, '+00:00 ');
            if (19 !== $offsetTimezone) { // 19 is strlen of 'YYYY-mm-ddTHH:ii:ss'
                continue;
            }

            $lineDate = substr($line, 0, $offsetTimezone);
            $lineTime = strtotime($lineDate);
            // invalid date?
            if (false === $lineTime) {
                continue;
            }
            // process only new dates
            if ($lineTime < $maxTime) {
                continue;
            }

            $offsetPriority = $offsetTimezone + 7; // 7 is strlen of '+00:00 ', i.e. 19+7=26
            $offsetMessage = strpos($line, ': ', $offsetPriority);
            if (false === $offsetMessage) {
                continue; // hmmm
            }
            $linePriorityInfo = substr($line, $offsetPriority, $offsetMessage - $offsetPriority);
            list($linePriorityName, $linePriorityValue) = explode(' ', $linePriorityInfo);
            // skip manual debug lines in system.log
            if ('debug' == strtolower($linePriorityName)) {
                continue;
            }

            $offsetMessage += 2; // ': ' between $priority and $message
            if ('exception' == $type || $offsetMessage >= strlen($line)) {
                // read next line for message
                $line = fgets($fh);
                if (false == $line) {
                    break; // EOF
                }
                $lineMessage = $line;
            } else {
                $lineMessage = substr($line, $offsetMessage);
            }
            $lineMessage = trim($lineMessage);

            $hash = md5($lineMessage);
            if (isset($logs[$hash])) {
                $logs[$hash]['qty']++;
                if ($lineTime > $logs[$hash]['last_time']) {
                    $logs[$hash]['last_time'] = $lineTime;
                }
                $logs[$hash]['times'][] = $lineTime;
            } else {
                $logs[$hash] = array(
                    'hash' => $hash,
                    'message' => $lineMessage,
                    'qty' => 1,
                    'last_time' => $lineTime,
                    'times' => array($lineTime),
                );
            }
        }

        fclose($fh);

        // calculate first_time
        foreach ($logs as $hash => $_log) {
            $_times = $_log['times'];
            sort($_times);
            $logs[$hash]['first_time'] = current($_times);
        }


        list($inserted, $updated) = $this->getResource()->saveLogs($type, $logs);

        return ($inserted+$updated);
    }

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
