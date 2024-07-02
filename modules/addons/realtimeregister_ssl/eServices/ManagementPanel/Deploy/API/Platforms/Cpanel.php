<?php

declare(strict_types=1);

namespace MGModule\RealtimeRegisterSsl\eServices\ManagementPanel\Deploy\Api\Platforms;

use GuzzleHttp\Exception\GuzzleException;
use MGModule\RealtimeRegisterSsl\eServices\ManagementPanel\Client\Client;
use MGModule\RealtimeRegisterSsl\mgLibs\exceptions\DeployException;
use function GuzzleHttp\Psr7\build_query;

class Cpanel extends Client implements PlatformInterface
{
    private int $port = 2087;

    //https://hostname.example.com:2087/cpsess##########/json-api/installssl?api.version=1

    private array $uri = [
        'generate_key' => 'json-api/cpanel?cpanel_jsonapi_apiversion=3&cpanel_jsonapi_module=SSL&cpanel_jsonapi_func=generate_key',
        'list_keys' => 'json-api/cpanel?cpanel_jsonapi_apiversion=3&cpanel_jsonapi_module=SSL&cpanel_jsonapi_func=list_keys',
        'generate_csr' => 'json-api/cpanel?cpanel_jsonapi_apiversion=3&cpanel_jsonapi_module=SSL&cpanel_jsonapi_func=generate_csr',
        'ssl_install_ssl' => 'json-api/installssl?api.version=3',
        'show_key' => 'json-api/cpanel?cpanel_jsonapi_apiversion=3&cpanel_jsonapi_module=SSL&cpanel_jsonapi_func=show_key',
    ];

    private string $contentType = "Content-Type: application/x-www-form-urlencoded";

    public function __construct(array $params)
    {
        $params['contentType'] = $this->contentType;
        $params['API_PORT'] = $params['API_PORT'] ?: $this->port;
        $params['API_URL'] = sprintf("%s/", $params['API_URL']);
        $params['debug'] = 'on';
        parent::__construct($params);
    }

    /**
     * @throws DeployException
     */
    public function genKeyCsr(string $domain, array $csrData): array
    {
        $name = $domain . "_realtimeregister_ssl_autodeploy";

        $key = $this->generateKey($name, 2048);
        if (!$key) {
            throw new DeployException("Can't generate key. Unknown Error");
        }

        $csr = $this->generateCsr($domain, $key['id'], $name, $csrData);

        return ["status" => "success", "key" => $key['key'], "csr" => $csr, 'keyId' => $key['id']];
    }

    /**
     * @throws DeployException
     */
    public function generateKey(string $name, int $keysize): array
    {
        $args = [
            'keysize' => $keysize,
            'friendly_name' => $name,
        ];

        try {
            $response = $this->url($this->uri['generate_key'])->request('POST', $args);
        } catch (DeployException $ex) {
            throw new DeployException($ex->getMessage());
        }

        if (isset($response->result->errors)) {
            throw new DeployException($response->result->errors[0]);
        }

        return ['id' => $response->result->data->id, 'key' => $response->result->data->text];
    }

    /**
     * @throws DeployException
     */
    public function generateCsr(string $domain, string $keyid, string $keyname, array $csrData): string
    {
        $args = [
            'domains' => $domain,
            'countryName' => $csrData['short_country'],
            'stateOrProvinceName' => $csrData['state'],
            'localityName' => $csrData['city'],
            'organizationName' => $csrData['company'],
            'organizationalUnitName' => $csrData['department'],
            'emailAddress' => $csrData['approverEmail'],
            'key_id' => $keyid,
            'friendly_name' => $keyname,
        ];

        try {
            $response = $this->url([$this->uri['generate_csr']], false, true)->request('POST', $args);
        } catch (DeployException $ex) {
            throw new DeployException($ex->getMessage());
        }

        if (isset($response->result->errors)) {
            throw new DeployException($response->result->errors[0]);
        }

        return $response->result->data->text;
    }

    /**
     * @return string
     * @throws DeployException
     */
    public function uploadCertificate(string $domain, string $crt)
    {
        return "success";
    }

    /**
     * @throws DeployException
     */
    public function getKey(string $domain, string $id): string
    {
        $name = $domain . "_realtimeregister_ssl_autodeploy";
        $args = [
            'id' => $id,
            'friendly_name' => $name,
        ];

        try {
            //$response = $this->url([$this->uri['show_key']], false, true)->request('POST', $args);
        } catch (DeployException $ex) {
            throw new DeployException($ex->getMessage());
        }

        if (isset($response->result->errors)) {
            throw new DeployException($response->result->errors[0]);
        }

        return $response->result->data->key;
    }

    /**
     * @param $domain
     * @param $key
     * @param $crt
     * @param $ca
     * @return string
     * @throws DeployException|GuzzleException
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

        $response = $this->request($this->url($url));

        if ($response['metadata']['result'] != 1) {
            throw new DeployException($response['metadata']['reason']);
        }

        return "success";
    }

    protected function getAuth() : array
    {
        return [$this->args['API_USER'], $this->args['API_PASSWORD']];
    }

    /**
     * @param mixed $response
     * @return mixed
     * @throws DeployException
     */
    protected function parseResponse($response)
    {
        $result = json_decode($response);
        if (isset($result->cpanelresult, $result->cpanelresult->error)) {
            if (isset($result->cpanelresult->data->reason)) {
                throw new DeployException($result->cpanelresult->data->reason);
            } else {
                throw new DeployException($result->cpanelresult->error);
            }
        }

        return $result;
    }

    protected function getBaseUrl() : string
    {
        return substr($this->args['API_URL'], 0, strlen($this->args['API_URL']) -1)
            . ':'
            . $this->args['API_PORT']
            . '/';
    }
}
