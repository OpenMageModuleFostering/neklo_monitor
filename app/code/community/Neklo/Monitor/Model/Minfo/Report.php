<?php

/**
 * @method Neklo_Monitor_Model_Resource_Minfo_Report getResource()
 */
class Neklo_Monitor_Model_Minfo_Report extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        $this->_init('neklo_monitor/minfo_report');
    }

    public function generateReports($files)
    {
        $reports = array();

//        $maxMtime = $this->getResource()->fetchMaxMtime();
        foreach ($files as $_mtime => $_files) {
//            if ($_mtime < $maxMtime) {
//                continue;
//            }
            foreach ($_files as $_name => $_file) {
                /** @var SplFileInfo $_file */
                $_path = $_file->getPathname();
                $_dataSer = file_get_contents($_path);
                if ('a:5:{i:0;s:' != substr($_dataSer, 0, 11)) {
                    continue;
                }
                $_data = @unserialize($_dataSer);
                if ($_data && is_array($_data)) {
                    $message = $_data[0];
                    $hash = md5($message);
                    if (isset($reports[$hash])) {
                        $reports[$hash]['qty']++;
                        if ($_mtime > $reports[$hash]['last_time']) {
                            $reports[$hash]['last_time'] = $_mtime;
                        }
                        $reports[$hash]['files'][] = array(
                            'name' => ''.$_name,
                            'path' => ''.$_path,
                            'time' => (int) $_mtime,
                            'size' => (int) $_file->getSize(),
                        );
                    } else {
                        $reports[$hash] = array(
                            'hash' => $hash,
                            'message' => $message,
                            'qty' => 1,
                            'last_time' => $_mtime,
                            'files' => array(
                                array(
                                    'name' => ''.$_name,
                                    'path' => ''.$_path,
                                    'time' => (int) $_mtime,
                                    'size' => (int) $_file->getSize(),
                                )
                            ),
                        );
                    }
                }
            }

            // calculate first_mtime
            foreach ($reports as $hash => $_report) {
                $minTime = time()*2; // server file mtime (incl. locale timezone) might be bigger than now GMT
                foreach ($_report['files'] as $_file) {
                    if ($_file['time'] < $minTime) {
                        $minTime = $_file['time'];
                    }
                }
                $reports[$hash]['first_time'] = $minTime;
            }
        }

        list($inserted, $updated) = $this->getResource()->saveReports($reports);

        return ($inserted+$updated);
    }

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

}