<?php

if (!defined('DS')) {
    define('DS',DIRECTORY_SEPARATOR);
}

require_once __DIR__ . DS . 'Loader.php';
new \MGModule\RealtimeRegisterSsl\Loader();

#MGLICENSE_FUNCTIONS#

function realtimeregister_ssl_config(){
    return MGModule\RealtimeRegisterSsl\Addon::config();
}

function realtimeregister_ssl_activate(){
    return MGModule\RealtimeRegisterSsl\Addon::activate();
}

function realtimeregister_ssl_deactivate(){
    return MGModule\RealtimeRegisterSsl\Addon::deactivate();
}

function realtimeregister_ssl_upgrade($vars){
    return MGModule\RealtimeRegisterSsl\Addon::upgrade($vars);
}

function realtimeregister_ssl_output($params){
    #MGLICENSE_CHECK_ECHO_AND_RETURN_MESSAGE#
    MGModule\RealtimeRegisterSsl\Addon::I(FALSE,$params);
    
    if (!empty($_REQUEST['json'])) {
        ob_clean();
        header('Content-Type: text/plain');
        echo MGModule\RealtimeRegisterSsl\Addon::getJSONAdminPage($_REQUEST);
        die();
    }
    
    if (!empty($_REQUEST['customPage'])) {
        ob_clean();
        echo MGModule\RealtimeRegisterSsl\Addon::getHTMLAdminCustomPage($_REQUEST);
        die();
    }

    echo MGModule\RealtimeRegisterSsl\Addon::getHTMLAdminPage($_REQUEST);
}

function realtimeregister_ssl_clientarea(){
    #MGLICENSE_CHECK_ECHO_AND_RETURN_MESSAGE#

    if (!empty($_REQUEST['json'])) {
        ob_clean();
        header('Content-Type: text/plain');
        echo MGModule\RealtimeRegisterSsl\Addon::getJSONClientAreaPage($_REQUEST);
        die();
    }
    
    return MGModule\RealtimeRegisterSsl\Addon::getHTMLClientAreaPage($_REQUEST);
}
