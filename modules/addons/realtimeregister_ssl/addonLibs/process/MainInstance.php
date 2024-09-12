<?php

namespace AddonModule\RealtimeRegisterSsl\addonLibs\process;
use AddonModule\RealtimeRegisterSsl as main;

/**
 * Description of mainController
 *
 * @SuppressWarnings(PHPMD)
 */
class MainInstance
{
    /**
     *
     * @var abstractMainDriver
     */
    static private $_instanceName;

    public static function setInstanceName($instance)
    {
        self::$_instanceName = $instance;
    }
    
    public static function __callStatic($name, $arguments)
    {
        return call_user_func([self::$_instanceName,$name],$arguments);
    }

    /**
     * 
     * @return main\addonLibs\process\AbstractMainDriver
     * @throws exceptions\System
     */
    static function I()
    {
        if(empty(self::$_instanceName)) {
            throw new main\addonLibs\exceptions\System('Instance is not set');
        }
        return call_user_func([self::$_instanceName,'I']);
    }
}
