<?php

class Neklo_Monitor_Helper_Var_Report extends Mage_Core_Helper_Data
{
    const VAR_REPORT = 'report';

    public function collectReports()
    {
        $fileList = $this->_collectReportFiles();
        $reportList = $this->_prepareFileData($fileList);
        return $reportList;
    }

    protected function _collectReportFiles()
    {
        $directoryPath = Mage::getBaseDir('var') . DS . self::VAR_REPORT . DS;
        if (!is_dir($directoryPath) || ! is_readable($directoryPath)) {
            return false;
        }

        $lastCollectedAt = $this->_getConfig()->getCronReportCollectedAt();

        $count = 0;
        $directoryIterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directoryPath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST,
            RecursiveIteratorIterator::CATCH_GET_CHILD
        );
        $fileList = array();
        foreach ($directoryIterator as $file) {
            /* @var SplFileInfo $file */
            if (!$file->isFile()) {
                continue;
            }
            if ($lastCollectedAt >= $file->getMTime()) {
                continue;
            }
            $fileList[] = $file;
            $count++;
        }
        $this->_getConfig()->updateCronReportCollectedAt();

        return $fileList;
    }

    protected function _prepareFileData(array $fileList)
    {
        $preparedFileList = array();

        /* @var SplFileInfo $file */
        foreach ($fileList as $file) {
            $reportContent = file_get_contents($file->getPathname());
            $reportData = @unserialize($reportContent);
            if (!$reportData || !is_array($reportData) || !count($reportData)) {
                continue;
            }

            $message = $reportData[0];
            $hash = md5($message);
            if (array_key_exists($hash, $preparedFileList) && is_array($preparedFileList[$hash])) {
                $preparedFileList[$hash]['qty']++;
                if ($file->getMTime() < $preparedFileList[$hash]['first_time']) {
                    $preparedFileList[$hash]['first_time'] = $file->getMTime();
                }
                if ($file->getMTime() > $preparedFileList[$hash]['last_time']) {
                    $preparedFileList[$hash]['last_time'] = $file->getMTime();
                }
                $preparedFileList[$hash]['files'][] = array(
                    'name' => $file->getFilename(),
                    'path' => $file->getPathname(),
                    'time' => $file->getMTime(),
                    'size' => $file->getSize(),
                );
            } else {
                $preparedFileList[$hash] = array(
                    'hash'       => $hash,
                    'message'    => $message,
                    'qty'        => 1,
                    'first_time' => $file->getMTime(),
                    'last_time'  => $file->getMTime(),
                    'files'      => array(
                        array(
                            'name' => $file->getFilename(),
                            'path' => $file->getPathname(),
                            'time' => $file->getMTime(),
                            'size' => $file->getSize(),
                        )
                    ),
                );
            }
        }
        return $preparedFileList;
    }

    /**
     * @return Neklo_Monitor_Helper_Config
     */
    protected function _getConfig()
    {
        return Mage::helper('neklo_monitor/config');
    }
}