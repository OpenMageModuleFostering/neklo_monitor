<?php

class Neklo_Monitor_Autoload
{
    static protected $_instance;
    protected static $registered = false;

    static public function instance()
    {
        if (!self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    static public function register()
    {
        if (self::$registered) {
            return;
        }
        spl_autoload_register(array(self::instance(), 'autoload'), false, true);
        self::$registered = true;
    }

    public function autoload($class)
    {
        $classFile = str_replace('\\', '/', $class) . '.php';
        if (strpos($classFile, '/') !== false) {
            include $classFile;
        }
    }
}
