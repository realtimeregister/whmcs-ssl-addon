<?php

namespace AddonModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl;

class WebServers
{
    public static function getAll($id)
    {
        $webServers = \AddonModule\RealtimeRegisterSsl\eProviders\ApiProvider::getInstance()->getApi()->getWebServers($id);
        return $webServers = $webServers['webservers'];
    }
}
