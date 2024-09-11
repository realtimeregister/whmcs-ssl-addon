<?php

namespace AddonModule\RealtimeRegisterSsl\addonLibs\process;
use AddonModule\RealtimeRegisterSsl as main;

/**
 * Description of abstractController
 *
 * @SuppressWarnings(PHPMD)
 */
abstract class AbstractController
{
    public $addonToken = null;
    private $registredValidationErros = [];
            
    function __construct($input = null)
    {
        if(isset($input['addon-token'])) {
            $this->addonToken = $input['addon-token'];
        }
    }
    
    /**
     * Generate Token For Form
     *
     * @return string
     */
    function genToken()
    {
        return md5(time());
    }
    
    /**
     * Validate Token With previous checked
     *
     * @param string $token
     * @return boolean
     */
    function checkToken($token = null)
    {
        if($token === null) {
            $token = $this->addonToken;
        }
        
        if($_SESSION['addon-token'] === $token) {
            return false;
        }
        
        $_SESSION['addon-token'] = $token;
        
        return true;
    }
    
    function dataTablesParseRow($template,$data)
    {
        $row = main\addonLibs\Smarty::I()->view($template,$data);
        
        $output = [];
        
        if (preg_match_all('/\<td\>(?P<col>.*?)\<\/td\>/s', $row, $result)) {
            foreach($result['col'] as $col) {
                $output[] = $col;
            }
        }
                
        return $output;
    }
    
    function registerErrors($errors)
    {
        $this->registredValidationErros = $errors;
    }
    
    function getFieldError($field,$langspace='validationErrors')
    {
        if (!isset($this->registredValidationErros[$field])) {
            return false;
        }
        
        $message = [];
        foreach ($this->registredValidationErros[$field] as $type) {
            $message[] = main\addonLibs\Lang::absoluteT($langspace,$type);
        }
        
        return implode(',',$message);
    }
    
    public function isActive()
    {
        return true;
    }
}
