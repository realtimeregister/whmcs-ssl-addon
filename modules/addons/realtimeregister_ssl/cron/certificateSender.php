<?php

define('DS', DIRECTORY_SEPARATOR); 
define('WHMCS_MAIN_DIR', substr(dirname(__FILE__),0, strpos(dirname(__FILE__),'modules'.DS.'addons')));  
define('ADDON_DIR', substr(dirname(__FILE__), 0, strpos(dirname(__FILE__), DS.'cron')));

require_once WHMCS_MAIN_DIR.DS.'init.php';

require_once ADDON_DIR.DS.'Loader.php';
$loader = new \AddonModule\RealtimeRegisterSsl\Loader();
$input = [];
$input['argv'] = $argv ? $argv : $_SERVER['argv']; 
\AddonModule\RealtimeRegisterSsl\Addon::cron($input, 'certificateSend');
