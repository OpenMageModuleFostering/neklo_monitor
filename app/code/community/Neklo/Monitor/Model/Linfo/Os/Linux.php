<?php

class Neklo_Monitor_Model_Linfo_Os_Linux extends \Linfo\OS\Linux
{
    protected $exec = null;

    public function __construct(array $settings)
    {
        parent::__construct($settings);
        $this->exec = new \Linfo\Parsers\CallExt();
        $this->exec->setSearchPaths(array('/sbin', '/bin', '/usr/bin', '/usr/local/bin', '/usr/sbin'));
    }

    public function getConnection()
    {
        if (!empty($this->settings['connection'])) {
            $t = new \Linfo\Meta\Timer('Connection');
        }
        $result = array();
        try {
            $command = $this->exec->exec('curl', ' -so /dev/null -w "connect:%{time_connect};ttfb:%{time_starttransfer};total:%{time_total};" ' . Mage::getBaseUrl());
            $lines = explode(";", $command);
            $result = array();
            foreach ($lines as $line) {
                if (!$line) {
                    continue;
                }
                list($key, $value) = explode(':', $line);
                if (!$key || !$value) {
                    continue;
                }
                $result[$key] = $value;
            }
        } catch (Exception $e) {
            \Linfo\Meta\Errors::add('Linux Core', 'Failed running curl.');
        }
        return $result;
    }
}
