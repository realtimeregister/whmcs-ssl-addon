<?php

namespace MGModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl;

class WebServers
{
    public static function getAll($id)
    {
        $webServers = \MGModule\RealtimeRegisterSsl\eProviders\ApiProvider::getInstance()->getApi()->getWebServers($id);
        return $webServers = $webServers['webservers'];
    }
}
