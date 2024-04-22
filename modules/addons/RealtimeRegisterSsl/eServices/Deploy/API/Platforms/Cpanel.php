<?php

namespace MGModule\RealtimeRegisterSsl\eServices\Deploy\API\Platforms;

use MGModule\RealtimeRegisterSsl\eServices\Deploy\API\Client;
use MGModule\RealtimeRegisterSsl\mgLibs\exceptions\DeployException;

class Cpanel extends Client implements PlatformInterface
{
    private int $port = 2083;

    private array $uri = [
        'generate_key' =>
            'json-api/cpanel?cpanel_jsonapi_apiversion=3&cpanel_jsonapi_module=SSL&cpanel_jsonapi_func=generate_key',
        'list_keys' =>
            'json-api/cpanel?cpanel_jsonapi_apiversion=3&cpanel_jsonapi_module=SSL&cpanel_jsonapi_func=list_keys',
        'generate_csr' =>
            'json-api/cpanel?cpanel_jsonapi_apiversion=3&cpanel_jsonapi_module=SSL&cpanel_jsonapi_func=generate_csr',
        'ssl_upload_cert' =>
            'json-api/cpanel?cpanel_jsonapi_apiversion=3&cpanel_jsonapi_module=SSL&cpanel_jsonapi_func=upload_cert',
        'ssl_install_ssl' =>
            'json-api/cpanel?cpanel_jsonapi_apiversion=3&cpanel_jsonapi_module=SSL&cpanel_jsonapi_func=install_ssl',
        'show_key' =>
            'json-api/cpanel?cpanel_jsonapi_apiversion=3&cpanel_jsonapi_module=SSL&cpanel_jsonapi_func=show_key',
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
     * @param $domain
     * @param $csrData
     * @return array
     * @throws DeployException
     */
    public function genKeyCsr(string $domain, array $csrData): array
    {
        $name = $domain . "_hostcontrol_autodeploy";

        $key = $this->generateKey($name, 2048);
        if (!$key) {
            throw new DeployException("Cat't generate kye. Unknown Error");
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
            $response = $this->url([$this->uri['generate_key']], false, true)->request('POST', $args);
        } catch (DeployException $ex) {
            throw new DeployException($ex->getMessage());
        }

        if (isset($response->result->errors)) {
            throw new DeployException($response->result->errors[0]);
        }

        return ['id' => $response->result->data->id, 'key' => $response->result->data->text];
    }

    /**
     * @param string $domain
     * @param string $keyid
     * @param string $keyname
     * @param array $csrData
     * @return string
     * @throws DeployException
     */
    public function generateCsr(string $domain, $keyid, $keyname, array $csrData)
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
     * @param $domain
     * @param $crt
     * @return string
     * @throws DeployException
     */
    public function uploadCertificate(string $domain, $crt)
    {
        $name = $domain . "_hostcontrol_autodeploy";
        $args = [
            'crt' => $crt,
            'friendly_name' => $name,
        ];

        try {
            $response = $this->url([$this->uri['ssl_upload_cert']], false, true)->request('POST', $args);
        } catch (DeployException $ex) {
            throw new DeployException($ex->getMessage());
        }

        if (isset($response->result->errors)) {
            throw new DeployException($response->result->errors[0]);
        }

        return "success";
    }

    /**
     * @param string $domain
     * @param string $id
     * @return string
     * @throws DeployException
     */
    public function getKey(string $domain, $id)
    {
        $name = $domain . "_hostcontrol_autodeploy";
        $args = [
            'id' => $id,
            'friendly_name' => $name,
        ];

        try {
            $response = $this->url([$this->uri['show_key']], false, true)->request('POST', $args);
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
     * @param $ca
     * @return string
     * @throws DeployException
     */
    public function installCertificate(string $domain, $key, array $crt, $csr = null, $ca = null)
    {
        $args = [
            'domain' => $domain,
            'cert' => $crt,
            'key' => $key,
        ];
        if (isset($ca)) {
            $args['cabundle'] = $crt['ca'];
        }

        try {
            $response = $this->url([$this->uri['ssl_install_ssl']], false, true)->request('POST', $args);
        } catch (DeployException $ex) {
            throw new DeployException($ex->getMessage());
        }

        if (isset($response->cpanelresult->errors)) {
            throw new DeployException($response->cpanelresult->errors[0]);
        }

        return "success";
    }

    protected function setAuth(): void
    {
        array_unshift(
            $this->options[CURLOPT_HTTPHEADER],
            sprintf(
                "Authorization: Basic %s",
                base64_encode($this->params['API_USER'] . ":" . $this->params['API_PASSWORD'])
            )
        );
    }

    /**
     * @param mixed $response
     * @return mixed
     * @throws DeployException
     */
    protected function parseResponse($response)
    {
        $result = json_decode($response);
        if (isset($result->cpanelresult) && isset($result->cpanelresult->error)) {
            if (isset($result->cpanelresult->data->reason)) {
                throw new DeployException($result->cpanelresult->data->reason);
            } else {
                throw new DeployException($result->cpanelresult->error);
            }
        }

        return $result;
    }
}
