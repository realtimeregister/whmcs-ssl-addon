<?php

namespace AddonModule\RealtimeRegisterSsl\mgLibs\error;
use AddonModule\RealtimeRegisterSsl as main;

/**
 * Error Handler 
 *
 * @todo DON'T USE IN PRODUCTION MODULES
 *
 */
class Handler
{
    public const EXCEPTION_HANDLER = "handleException";
    public const ERROR_HANDLER = "handleError";
    public const SHUTDOWN_HANDLER = "handleShutdown";
    /**
     * @todo SECURITY DANGER 
     * set FALSE for production modules
     */
    public const VERBOSE = false;
    
    /**
     * Register Error Functions 
     *
     */
    public function register()
    {
        set_error_handler([$this, self::ERROR_HANDLER]);
        set_exception_handler([$this, self::EXCEPTION_HANDLER]);
        register_shutdown_function([$this, self::SHUTDOWN_HANDLER]);
    }
    
    public function handleException(\Exception $exception)
    {
        if(static::$VERBOSE) {
            echo "<pre>";
            print_r($exception);
            echo "</pre>";
        }
        
        if(method_exists($exception, 'getToken'))
        {
            die("Error type H: ".$exception->getToken());
        }
    }
    
    public function handleError($level, $message, $file = null, $line = null)
    {
        if ($line == 0) {
           return true;
        }

        if (strpos($file,'tpl.php') !== false) {
            return true;
        }

        
        if (in_array($level, [E_NOTICE,E_USER_NOTICE])) {
            return true;
        }

        throw new main\mgLibs\exceptions\syntaxError($message, $level, 0, $line, $file);
    }
    
    public function handleShutdown()
    {
        $error = error_get_last();
                
        if ($error) {
            if ($error['line'] == 0) {
               return true;
            }
            
            if (static::$VERBOSE) {
                echo "<pre>";
                print_r($error);
                echo "</pre>";
            }

            if (in_array($error['type'], [E_NOTICE,E_USER_NOTICE])) {
                    return true;
            }
            
            die("Error");
        }
    }
}
