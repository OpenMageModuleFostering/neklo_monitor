<?php

class Neklo_Monitor_Model_Linfo extends \Linfo\Linfo
{
    protected $_defaultConfig = array(
        'byte_notation' => 1024,
        'dates'         => Varien_Date::DATETIME_INTERNAL_FORMAT,
        'language'      => 'en',
        'show'          => array(
            'os'               => true,
            'distro'           => true,
            'load'             => true,
            'ram'              => true,
            'mounts'           => true,
            'network'          => true,
            'uptime'           => true,
            'cpu'              => true,
            'connection'       => true,
            'kernel'           => false,
            'ip'               => false,
            'hd'               => false,
            'mounts_options'   => false,
            'webservice'       => false,
            'phpversion'       => false,
            'process_stats'    => false,
            'hostname'         => true,
            'devices'          => false,
            'model'            => false,
            'numLoggedIn'      => false,
            'virtualization'   => false,
            'duplicate_mounts' => false,
            'temps'            => false,
            'raid'             => false,
            'battery'          => false,
            'sound'            => false,
            'wifi'             => false,
            'services'         => false,
        ),
        'hide'          => array(
            'filesystems'       => array(
                'tmpfs',
                'ecryptfs',
                'nfsd',
                'rpc_pipefs',
                'usbfs',
                'devpts',
                'fusectl',
                'securityfs',
                'fuse.truecrypt',
            ),
            'storage_devices'   => array(
                'gvfs-fuse-daemon',
                'none',
            ),
            'mountpoints_regex' => array(
                '/^\/.+$/s'
            ),
            'fs_mount_options'  => array(
                'ecryptfs',
            ),
        ),
        'cpu_usage'     => true,
        'show_errors'   => false,
    );

    protected $_infoMap = array(
        // General
        'timestamp'                          => 'server_created_at',

        // OS
        'OS'                                 => 'os/name',
        'HostName'                           => 'os/hostname',
        'Distro/name'                        => 'os/distro/name',
        'Distro/version'                     => 'os/distro/version',

        // RAM
        'RAM/total'                          => 'ram/total',
        'RAM/free'                           => 'ram/free',

        // Mounts
        'Mounts/*/size'                      => 'mount/*/total',
        'Mounts/*/free'                      => 'mount/*/free',
        'Mounts/*/free_percent'              => 'mount/*/free_percent',
        'Mounts/*/used'                      => 'mount/*/used',
        'Mounts/*/used_percent'              => 'mount/*/used_percent',

        // CPU
        'Load'                               => 'cpu/load',
        'CPUArchitecture'                    => 'cpu/architecture',
        'CPU/*/Vendor'                       => 'cpu/core/*/vendor',
        'CPU/*/Model'                        => 'cpu/core/*/model',
        'CPU/*/MHz'                          => 'cpu/core/*/mhz',
        'CPU/*/usage_percentage'             => 'cpu/core/*/usage_percent',

        // Network
        'Network Devices/*/state'            => 'network/*/state',
        'Network Devices/*/recieved/bytes'   => 'network/*/received/bytes',
        'Network Devices/*/recieved/errors'  => 'network/*/received/errors',
        'Network Devices/*/recieved/packets' => 'network/*/received/packets',
        'Network Devices/*/sent/bytes'       => 'network/*/sent/bytes',
        'Network Devices/*/sent/errors'      => 'network/*/sent/errors',
        'Network Devices/*/sent/packets'     => 'network/*/sent/packets',

        // UpTime
        'UpTime/bootedTimestamp'             => 'uptime/booted_timestamp',
        'UpTime/text'                        => 'uptime/booted_timestamp_text',

        // Connection
        'Connection/connect'                 => 'connection/connect',
        'Connection/ttfb'                    => 'connection/ttfb',
        'Connection/total'                   => 'connection/total',
    );

    public function &getInfo()
    {
        $timestamp = time();
        $info = parent::getInfo();
        $info['timestamp'] = $timestamp;

        $infoMap = $this->_prepareInfoMap($info);
        $result = $this->_applyInfoMap($info, $infoMap);

        return $result;
    }

    protected function _applyInfoMap($data, $infoMap)
    {
        $result = array();
        foreach ($infoMap as $linfoPath => $monitorPath) {

            $linfoPathList = explode('/', $linfoPath);
            $linfoKeyValue = $data;
            foreach ($linfoPathList as $key) {
                if (!array_key_exists($key, $linfoKeyValue)) {
                    continue;
                }
                $linfoKeyValue = $linfoKeyValue[$key];
            }

            $monitorPathList = explode('/', $monitorPath);
            $monitorKeyValue = &$result;
            foreach ($monitorPathList as $key) {
                if (!array_key_exists($key, $monitorKeyValue)) {
                    $monitorKeyValue[$key] = array();
                }
                $monitorKeyValue = &$monitorKeyValue[$key];
            }
            $monitorKeyValue = $linfoKeyValue;
        }
        return $result;
    }

    protected function _prepareInfoMap($data)
    {
        $_infoMap = array();
        foreach ($this->_infoMap as $linfoPath => $monitorPath) {
            if (strpos($linfoPath, '*') === false) {
                $_infoMap[$linfoPath] = $monitorPath;
                continue;
            }

            $linfoPathList = explode('/', $linfoPath);
            $linfoKeyValue = $data;
            foreach ($linfoPathList as $key) {
                if ($key == '*') {
                    $realInfoKeyList = array_keys($linfoKeyValue);
                    foreach ($realInfoKeyList as $realInfoKey) {
                        $_infoMap[str_replace('*', $realInfoKey, $linfoPath)] = str_replace('*', $realInfoKey, $monitorPath);
                    }
                    continue;
                }
                if (!array_key_exists($key, $linfoKeyValue)) {
                    continue;
                }
                $linfoKeyValue = $linfoKeyValue[$key];
            }
        }
        return $_infoMap;
    }

    public function scan()
    {
        parent::scan();
        $this->_scanAdditional();
    }

    protected function loadSettings($settings = array())
    {
        if (!is_array($settings) || !count($settings)) {
            $settings = $this->_defaultConfig;
        }

        // Running unit tests?
        if (defined('LINFO_TESTING')) {
            $this->settings = \Linfo\Common::getVarFromFile($this->linfo_testdir.'/test_settings.php', 'settings');
            if (!is_array($this->settings)) {
                throw new \Linfo\Exceptions\FatalException('Failed getting test-specific settings');
            }
            return;
        }

        // Don't just blindly assume we have the ob_* functions...
        if (!function_exists('ob_start')) {
            $settings['compress_content'] = false;
        }

        if (!isset($settings['hide'])) {
            $settings['hide'] = array(
                'filesystems' => array(),
                'storage_devices' => array(),
            );
        }

        // Make sure these are arrays
        $settings['hide']['filesystems'] = is_array($settings['hide']['filesystems']) ? $settings['hide']['filesystems'] : array();
        $settings['hide']['storage_devices'] = is_array($settings['hide']['storage_devices']) ? $settings['hide']['storage_devices'] : array();

        // Make sure these are always hidden
        $settings['hide']['filesystems'][] = 'rootfs';
        $settings['hide']['filesystems'][] = 'binfmt_misc';

        // Default timeformat
        $settings['dates'] = array_key_exists('dates', $settings) ? $settings['dates'] : 'm/d/y h:i A (T)';

        // Default to english translation if garbage is passed
        if (empty($settings['language']) || !preg_match('/^[a-z]{2}$/', $settings['language'])) {
            $settings['language'] = 'en';
        }

        // If it can't be found default to english
        if (!is_file($this->linfo_localdir.'lib/Linfo/Lang/'.$settings['language'].'.php')) {
            $settings['language'] = 'en';
        }

        $this->settings = $settings;
    }

    protected function loadLanguage()
    {
        // Running unit tests?
        if (defined('LINFO_TESTING')) {
            $this->lang = require $this->linfo_testdir.'/test_lang.php';
            if (!is_array($this->lang)) {
                throw new \Linfo\Exceptions\FatalException('Failed getting test-specific language');
            }

            return;
        }

        // Load translation, defaulting to english of keys are missing (assuming
        // we're not using english anyway and the english translation indeed exists)
        if (is_file($this->linfo_localdir.'lib/Linfo/Lang/en.php') && $this->settings['language'] != 'en') {
            $this->lang = array_merge(require($this->linfo_localdir.'lib/Linfo/Lang/en.php'),
                require($this->linfo_localdir.'lib/Linfo/Lang/'.$this->settings['language'].'.php'));
        }

        // Otherwise snag desired translation, be it english or a non-english without english to fall back on
        else {
            $this->lang = require $this->linfo_localdir.'lib/Linfo/Lang/'.$this->settings['language'].'.php';
        }
    }

    protected function _scanAdditional()
    {
        $os = $this->getOS();

        if (!$os) {
            throw new \Linfo\Exceptions\FatalException('Unknown/unsupported operating system');
        }

        $parser = Mage::getModel('neklo_monitor/linfo_os_' . strtolower($os), $this->settings);

        $reflector = new ReflectionClass($parser);

        $fields = array(
            'Connection' => array(
                'show'    => !empty($this->settings['show']['connection']),
                'default' => '',
                'method'  => 'getConnection',
            ),
        );

        foreach ($fields as $key => $data) {
            if (!$data['show']) {
                $this->info[$key] = $data['default'];
                continue;
            }

            try {
                $method = $reflector->getMethod($data['method']);
                $this->info[$key] = $method->invoke($parser);
            } catch (ReflectionException $e) {
                $this->info[$key] = $data['default'];
            }
        }
    }
}
