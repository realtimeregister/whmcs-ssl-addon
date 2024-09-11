<?php

namespace AddonModule\RealtimeRegisterSsl\models\whmcs\errors;

/**
 * Register Error in WHMCS Module Log
 *
 * @SuppressWarnings(PHPMD)
 */
class Register extends \AddonModule\RealtimeRegisterSsl\mgLibs\models\Orm
{
    /**
     * Register Exception in WHMCS Module Log
     *
     * @param Exception $ex
     */
    static function register($ex)
    {
        $token = 'Unknow Token';

        if (method_exists($ex, 'getToken')) {
            $token = $ex->getToken();
        }

        $debug = print_r($ex, true);

        \logModuleCall("MGError", __NAMESPACE__, [
            'message' => $ex->getMessage(),
            'code' => $ex->getCode(),
            'token' => $token
        ], $debug, 0, 0);
    }
}
