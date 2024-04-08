<?php

if(!defined('DS'))define('DS',DIRECTORY_SEPARATOR);

#MGLICENSE_FUNCTIONS#

function RealtimeRegisterSsl_config(){
    require_once __DIR__ . DS . 'Loader.php';
    new \MGModule\RealtimeRegisterSsl\Loader();
    return MGModule\RealtimeRegisterSsl\Addon::config();
}

function RealtimeRegisterSsl_activate(){
    require_once __DIR__ . DS . 'Loader.php';
    new \MGModule\RealtimeRegisterSsl\Loader();
    return MGModule\RealtimeRegisterSsl\Addon::activate();
}

function RealtimeRegisterSsl_deactivate(){
    require_once __DIR__ . DS . 'Loader.php';
    new \MGModule\RealtimeRegisterSsl\Loader();
    return MGModule\RealtimeRegisterSsl\Addon::deactivate();
}

function RealtimeRegisterSsl_upgrade($vars){
    require_once __DIR__ . DS . 'Loader.php';
    new \MGModule\RealtimeRegisterSsl\Loader();
    return MGModule\RealtimeRegisterSsl\Addon::upgrade($vars);
}

function RealtimeRegisterSsl_output($params){
    require_once __DIR__ . DS . 'Loader.php';
    new \MGModule\RealtimeRegisterSsl\Loader();
    #MGLICENSE_CHECK_ECHO_AND_RETURN_MESSAGE#
    MGModule\RealtimeRegisterSsl\Addon::I(FALSE,$params);
    
    if(!empty($_REQUEST['json']))
    {
        ob_clean();
        header('Content-Type: text/plain');
        echo MGModule\RealtimeRegisterSsl\Addon::getJSONAdminPage($_REQUEST);
        die();
    }
    
    if(!empty($_REQUEST['customPage']))
    {
        ob_clean();
        echo MGModule\RealtimeRegisterSsl\Addon::getHTMLAdminCustomPage($_REQUEST);
        die();
    }

    echo MGModule\RealtimeRegisterSsl\Addon::getHTMLAdminPage($_REQUEST);
}


function RealtimeRegisterSsl_clientarea(){
    require_once __DIR__ . DS . 'Loader.php';
    new \MGModule\RealtimeRegisterSsl\Loader();
    
    #MGLICENSE_CHECK_ECHO_AND_RETURN_MESSAGE#
    
    if(!empty($_REQUEST['json']))
    {
        ob_clean();
        header('Content-Type: text/plain');
        echo MGModule\RealtimeRegisterSsl\Addon::getJSONClientAreaPage($_REQUEST);
        die();
    }
    
    return MGModule\RealtimeRegisterSsl\Addon::getHTMLClientAreaPage($_REQUEST);
}
