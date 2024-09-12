<?php

namespace AddonModule\RealtimeRegisterSsl\addonLibs\models;
use AddonModule\RealtimeRegisterSsl as main;

/**
 * Description of abstractModel
 *
 * @SuppressWarnings(PHPMD)
 */
abstract class Base
{
    /**
     * Normalized Time Stamp
     *
     * @param string $strTime
     * @return string
     */
    static function timeStamp($strTime = 'now')
    {
        return date('Y-m-d H:i:s',  strtotime($strTime));
    }
    
    /**
     * Disable Get Function
     *
     * @param string $property
     * @throws main\addonLibs\exceptions\System
     */
    function __get($property)
    {
        throw new main\addonLibs\exceptions\System(
            'Property: '.$property.' does not exits in: '.get_called_class(),
            main\addonLibs\exceptions\Codes::PROPERTY_NOT_EXISTS
        );
    }
    
    /**
     * Disable Set Function
     *
     * @param string $property
     * @param string $value
     * @throws main\addonLibs\exceptions\System
     */
    function __set($property, $value)
    {
        throw new main\addonLibs\exceptions\System(
            'Property: '.$property.' does not exits in: '.get_called_class(),
            main\addonLibs\exceptions\Codes::PROPERTY_NOT_EXISTS
        );
    }
    
    /**
     * Disable Call Function
     *
     * @param string $function
     * @param string $params
     * @throws main\addonLibs\exceptions\System
     */
    function __call($function, $params)
    {
        throw new main\addonLibs\exceptions\System(
            'Function: '.$function.' does not exits in: '.get_called_class(),
            main\addonLibs\exceptions\Codes::PROPERTY_NOT_EXISTS
        );
    }
    
    /**
     * Cast To array
     * 
     * @param string $container
     * @return array
     */
    function toArray($container = true)
    {
        $className = get_called_class();
        
        $fields = get_class_vars($className);

        foreach (explode('\\', $className) as $className);
        
        $data = [];
        
        foreach ($fields as $name => $defult) {
            if(isset($this->{$name})) {
                $data[$name] = $this->{$name};
            }
        }

        if ($container === true) {
            return [
                $className => $data
            ];
        } elseif($container) {
            return [
                $container => $data
            ];
        } else {
            return $data;
        }
    }
    
    /**
     * Encrypt String using Hash from configration
     *
     * @param string $input
     * @return string
     */
    static function encrypt($input)
    {
        if (empty($input)) {
            return false;
        }
                        
        return base64_encode(
            mcrypt_encrypt(
                MCRYPT_RIJNDAEL_256,
                main\addonLibs\process\MainInstance::I()->getEncryptKey(),
                $input,
                MCRYPT_MODE_ECB)
        );
    }
    
    /**
     * Decrypt String using Hash from configration
     *
     * @param string $input
     * @return string
     */
    function decrypt($input)
    {
        if(empty($input)) {
            return false;
        }
                
        return trim(
            mcrypt_decrypt(
                MCRYPT_RIJNDAEL_256,
                main\addonLibs\process\MainInstance::I()->getEncryptKey(),
                base64_decode($input),
                MCRYPT_MODE_ECB
            )
        );
    }
    
    function serialize($input)
    {
        return base64_encode(serialize($input));
    }
    
    function unserialize($input)
    {
        return unserialize(base64_decode($input));
    }
}
