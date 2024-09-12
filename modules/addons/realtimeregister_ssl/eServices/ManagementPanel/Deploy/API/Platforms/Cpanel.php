<?php

declare(strict_types=1);

namespace AddonModule\RealtimeRegisterSsl\eServices\ManagementPanel\Deploy\Api\Platforms;

use GuzzleHttp\Exception\GuzzleException;
use AddonModule\RealtimeRegisterSsl\eServices\ManagementPanel\Client\Client;
use AddonModule\RealtimeRegisterSsl\addonLibs\exceptions\DeployException;
use function GuzzleHttp\Psr7\build_query;

class Cpanel extends Client implements PlatformInterface
{
    private int $port = 2087;

    private array $uri = [
        'ssl_install_ssl' => 'json-api/installssl?api.version=3',
    ];

    private string $contentType = "Content-Type: application/x-www-form-urlencoded";

    public function __construct(array $params)
    {
        $params['contentType'] = $this->contentType;
        $params['API_PORT'] = $params['API_PORT'] ?: $this->port;
        $params['debug'] = 'on';
        parent::__construct($params);
    }

    /**
     * @return string
     */
    public function uploadCertificate(string $domain, string $crt)
    {
        return "success";
    }

    /**
     * @param $domain
     * @param $key
     * @param $crt
     * @param null $csr
     * @param null $ca
     * @return string
     * @throws GuzzleException
     */
    public function installCertificate($domain, $key, $crt, $csr = null, $ca = null): string
    {
        $args = [
           'domain' => $domain,
           'crt' => $crt,
           'key' => $key
        ];
        if (isset($ca)) {
            $args['cabundle'] = $ca;
        }

        $url = $this->uri['ssl_install_ssl']  . '&' . build_query($args);

        $this->request($this->url($url));

        return "success";
    }

    protected function getAuth() : array
    {
        return [$this->args['API_USER'], $this->args['API_PASSWORD']];
    }

    protected function getBaseUrl() : string
    {
        return $this->args['API_URL']
            . ':'
            . $this->args['API_PORT']
            . '/';
    }

    /**
     * @throws DeployException
     */
    protected function parseResponse(string $response) {
        $result =  json_decode($response, true);

        if ($result['metadata']['result'] != 1) {
            throw new DeployException($result['metadata']['reason']);
        }
    }
}
