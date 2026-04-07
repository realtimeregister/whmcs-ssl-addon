<?php

declare(strict_types=1);

namespace AddonModule\RealtimeRegisterSsl\eServices\ManagementPanel\Deploy\Api\Platforms;

use AddonModule\RealtimeRegisterSsl\eServices\ManagementPanel\Api\Panel\Client\Debug;
use GuzzleHttp\Exception\GuzzleException;
use AddonModule\RealtimeRegisterSsl\eServices\ManagementPanel\Client\Client;
use AddonModule\RealtimeRegisterSsl\addonLibs\exceptions\DeployException;

use GuzzleHttp\Psr7\Query;

class Cpanel extends Client implements PlatformInterface
{
    private int $port = 2083;

    private array $uri = [
        'ssl_install_ssl' => 'execute/SSL/install_ssl',
        'list_subdomains' => 'execute/DomainInfo/list_domains'
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
     * @throws DeployException
     */
    public function installCertificate($domain, $key, $crt, $csr = null, $ca = null): string
    {
        $domains = [$this->normalizeDomain($domain)];
        if ($this->isWildcard($domain)) {
            array_push($domains, ...$this->getAllSubDomains($this->normalizeDomain($domain)));
        }

        foreach ($domains as $domain) {
            $args = [
                'domain' => $this->normalizeDomain($domain),
                'cert' => $crt,
                'key' => $key
            ];
            if (isset($ca)) {
                $args['cab'] = $ca;
            }

            $url = $this->uri['ssl_install_ssl'] . '?' . Query::build($args);

            $this->request($this->url($url), 'POST');
        }

        return "success";
    }

    /**
     * @throws DeployException
     * @throws GuzzleException
     */
    protected function getAllSubDomains(string $domain) : array {
        $response = $this->request($this->uri['list_subdomains']);
        if ($response['errors']) {
            throw new DeployException($response['errors'][0]);
        }
        $subDomains = [];
        foreach ($response['data']['sub_domains'] ?? [] as $subDomain) {
            if (str_ends_with($subDomain, '.' . $domain)) {
                $subDomains[] = $subDomain;
            }
        }
        return $subDomains;
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
        $result = json_decode($response, true);

        if ($result['errors']) {
            throw new DeployException($result['errors'][0]);
        }

        return $result;
    }
}
