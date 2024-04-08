<?php

namespace MGModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl;

use Exception;

class WebServers {
    public static function getAll($id) {
        //$webServers = \MGModule\RealtimeRegisterSsl\eProviders\ApiProvider::getInstance()->getApi()->getWebServers($id);
        //return $webServers = $webServers['webservers'];
        
        return array(
            array('id' => '18', 'software' => 'IIS'),
            array('id' => '18', 'software' => 'Any Other')
        );
    }
}
