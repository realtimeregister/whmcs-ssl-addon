<?php

namespace HostcontrolSSL\Services\Client;

use AddonModule\RealtimeRegisterSsl\eRepository\whmcs\config\Config;

class Debug
{
    public function __construct($url, $method, $request, $response, $options)
    {
        if (method_exists($request, 'getPath')) {
            $url = $request->getPath();
        }
        $requestInfo = <<<REQUESTINFO
$method {$url}
{$this->debugLogRequest($request->getHeaders())}

$options
REQUESTINFO;

        $responseInfo = <<<RESPONSEINFO
HTTP/{$response->getProtocolVersion()} {$response->getStatusCode()} {$response->getReasonPhrase()}

{$this->debugLogRequest($response->getHeaders())}

{$response->getBody()}
RESPONSEINFO;

        logModuleCall(Config::get('addon.name'), $method, $requestInfo, $responseInfo);
    }

    private function debugLogRequest(array $array)
    {
        $urldecode = urldecode(http_build_query($array, '', '^^'));
        $responseHeadersExplode = explode('^^', str_replace("[0]=", ": ", $urldecode));
        return implode(PHP_EOL, $responseHeadersExplode);
    }
}
