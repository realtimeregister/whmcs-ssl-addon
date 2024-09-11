<?php

if (!defined('DS')) {
    define('DS',DIRECTORY_SEPARATOR);
}

require_once __DIR__ . DS . 'Loader.php';
new \AddonModule\RealtimeRegisterSsl\Loader();

#MGLICENSE_FUNCTIONS#

function realtimeregister_ssl_config(){
    return AddonModule\RealtimeRegisterSsl\Addon::config();
}

function realtimeregister_ssl_activate(){
    return AddonModule\RealtimeRegisterSsl\Addon::activate();
}

function realtimeregister_ssl_deactivate(){
    return AddonModule\RealtimeRegisterSsl\Addon::deactivate();
}

function realtimeregister_ssl_upgrade($vars){
    return AddonModule\RealtimeRegisterSsl\Addon::upgrade($vars);
}

function realtimeregister_ssl_output($params){
    #MGLICENSE_CHECK_ECHO_AND_RETURN_MESSAGE#
    AddonModule\RealtimeRegisterSsl\Addon::I(FALSE,$params);
    
    if (!empty($_REQUEST['json'])) {
        ob_clean();
        header('Content-Type: text/plain');
        echo AddonModule\RealtimeRegisterSsl\Addon::getJSONAdminPage($_REQUEST);
        die();
    }
    
    if (!empty($_REQUEST['customPage'])) {
        ob_clean();
        echo AddonModule\RealtimeRegisterSsl\Addon::getHTMLAdminCustomPage($_REQUEST);
        die();
    }

    echo AddonModule\RealtimeRegisterSsl\Addon::getHTMLAdminPage($_REQUEST);
}

function realtimeregister_ssl_clientarea(){
    #MGLICENSE_CHECK_ECHO_AND_RETURN_MESSAGE#

    if (!empty($_REQUEST['json'])) {
        ob_clean();
        header('Content-Type: text/plain');
        echo AddonModule\RealtimeRegisterSsl\Addon::getJSONClientAreaPage($_REQUEST);
        die();
    }
    
    return AddonModule\RealtimeRegisterSsl\Addon::getHTMLClientAreaPage($_REQUEST);
}
