<?php

namespace MGModule\RealtimeRegisterSsl\eServices\ManagementPanel\Api\Panel\Client;

class Debug
{
    public function __construct($method, $request, $response, $options)
    {
        $requestInfo = <<<REQUESTINFO
$method {$request->getPath()}
{$this->debugLogRequest($request->getHeaders())}

$options
REQUESTINFO;

        $responseJson = json_encode($response->json());

        $responseInfo = <<<RESPONSEINFO
HTTP/{$response->getProtocolVersion()} {$response->getStatusCode()} {$response->getReasonPhrase()}

{$this->debugLogRequest($response->getHeaders())}

$responseJson
RESPONSEINFO;

        logModuleCall("Hostcontrol Panel Client", $method, $requestInfo, $responseInfo);
    }

    private function debugLogRequest(array $array)
    {
        $urldecode = urldecode(http_build_query($array, '', '^^'));
        $responseHeadersExplode = explode('^^', str_replace("[0]=", ": ", $urldecode));
        return implode(PHP_EOL, $responseHeadersExplode);
    }
}
