<?php

namespace MGModule\RealtimeRegisterSsl\mgLibs\error;
use MGModule\RealtimeRegisterSsl as main;

/**
 * Description of error\register
 *
 * @SuppressWarnings(PHPMD)
 */
class Register
{
    static private $_errorRegister = null;
    
    static function setErrorRegisterClass($class)
    {
        self::$_errorRegister = $class;
    }

    static function register($ex)
    {
        if (self::$_errorRegister && class_exists(self::$_errorRegister,false)) {
            call_user_func([self::$_errorRegister,'register',$ex]);
        } elseif(
            class_exists(main\mgLibs\process\MainInstance::I()->getMainNamespace().'\models\whmcs\errors\Register')
        ) {
            call_user_func(
                [main\mgLibs\process\MainInstance::I()->getMainNamespace().'\models\whmcs\errors\Register','register'],
                $ex
            );
        } else {
            $token = 'Unknow Token';

            if (method_exists($ex, 'getToken')) {
                $token = $ex->getToken();
            }

            $debug = print_r($ex,true);

            \logModuleCall(
                "MGError",
                __NAMESPACE__,
                [
                    'message' => $ex->getMessage(),
                    'code' => $ex->getCode(),
                    'token' => $token
                ],
                $debug,
                0,
                0
            );
        }
    }
}
