<?php

namespace AddonModule\RealtimeRegisterSsl\eServices\ManagementPanel\Api\Panel\Client;

class Debug
{
    public function __construct($method, $type, $headers, $response, $options)
    {
        $requestInfo = <<<REQUESTINFO
        $method {$type}
        {$this->debugLogRequest($headers)}
        
        $options
        REQUESTINFO;

        $responseBody = (string) $response->getBody();
        $responseInfo = <<<RESPONSEINFO
        HTTP/{$response->getProtocolVersion()} {$response->getStatusCode()} {$response->getReasonPhrase()}
        
        {$this->debugLogRequest($response->getHeaders())}
        
        $responseBody
        RESPONSEINFO;

        logModuleCall("Realtime Register SSL Panel Client", $method, $requestInfo, $responseInfo);
    }

    private function debugLogRequest(array $array)
    {
        $urldecode = urldecode(http_build_query($array, '', '^^'));
        $responseHeadersExplode = explode('^^', str_replace("[0]=", ": ", $urldecode));
        return implode(PHP_EOL, $responseHeadersExplode);
    }
}
