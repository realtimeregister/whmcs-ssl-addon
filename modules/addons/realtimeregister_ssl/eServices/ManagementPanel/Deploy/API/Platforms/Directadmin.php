<?php

declare(strict_types=1);

namespace MGModule\RealtimeRegisterSsl\eServices\ManagementPanel\Deploy\API\Platforms;

use MGModule\RealtimeRegisterSsl\eServices\ManagementPanel\Client\Client;
use MGModule\RealtimeRegisterSsl\mgLibs\exceptions\DeployException;

class Directadmin extends Client implements PlatformInterface
{
    private int $port = 2222;

    private array $uri = [
        'CMD_API_SSL' => "/CMD_API_SSL",
        'CMD_SSL' => "/CMD_SSL",
    ];

    private string $contentType = "Content-Type: text/plain";

    public function __construct(array $params)
    {
        $params['contentType'] = $this->contentType;
        $params['API_PORT'] = $params['API_PORT'] ?? $this->port;
        $params['debug'] = 'on';
        parent::__construct($params);
    }

    /**
     * @return array [key, csr]
     * @throws DeployException
     */
    public function genKeyCsr(string $domain, array $csrData): array
    {
        $this->getKey($domain);
        $argc = [
            'domain' => $domain,
            'action' => "save",
            'type' => "create",
            'request' => "yes",
            'country' => $csrData['short_country'],
            'province' => $csrData['state'],
            'city' => $csrData['city'],
            'company' => $csrData['company'],
            'division' => $csrData['department'],
            'name' => $domain,
            'email' => $csrData['approverEmail'],
            'keysize' => "2048",
            'encryption' => "sha256",
        ];
        $url = $this->uri['CMD_API_SSL'] . "?" . http_build_query($argc);
        $response = $this->url([$url], false, true)->request('POST');
        parse_str($response, $output);

        if (isset($output['error']) && $output['error'] == 1) {
            throw new DeployException($output['text'] . $output['details']);
        }

        $csr = html_entity_decode($output['request'], ENT_QUOTES, 'UTF-8');

        if ($output['error'] == 0) {
            return ["status" => "success", "key" => "", "csr" => $csr];
        }

        return ["status" => "error", "message" => "Unknown Error"];
    }

    public function getKey(string $domain, string $id = null)
    {
        $url = $this->uri['CMD_API_SSL'] . "?" . http_build_query(['domain' => $domain]);

        try {
            $response = $this->url([$url], false, true)
                ->request('GET');
        } catch (DeployException $ex) {
            throw new DeployException($ex->getMessage());
        }
        parse_str($response, $output);

        if (isset($output['error']) && $output['error'] == 1) {
            throw new DeployException($output['text'] . $output['details']);
        }

        return $output['key'];
    }

    public function uploadCertificate(string $domain, string $crt)
    {
        return true;
    }

    /**
     * @return string
     * @throws DeployException
     */
    public function installCertificate(string $domain, string $key, string $crt, string $csr = null, string $ca = null)
    {
        if (!$key) {
            $key = $this->getKey($domain);
        }

        $argc = [
            'action' => "save",
            'domain' => $domain,
            'type' => "paste",
            'certificate' => $key . $crt,
            'submit' => 'Save',
        ];
        $url = $this->uri['CMD_API_SSL'] . "?" . http_build_query($argc);
        try {
            $response = $this->url([$url], false, true)
                ->request('POST', $argc);
        } catch (DeployException $ex) {
            throw new DeployException($ex->getMessage());
        }
        parse_str($response, $output);
        if (isset($output['error']) && $output['error'] == 1) {
            throw new DeployException($output['text'] . $output['details']);
        }

        if ($output['error'] == 0) {
            return "success";
        }

        return "Unknown Error";
    }


    /**
     * @return string|void
     */
    protected function setData(array $data)
    {
        if ($data['action'] == "upload") {
            array_unshift(
                $this->options[CURLOPT_HTTPHEADER],
                "X-DirectAdmin-File-Upload: yes",
                "X-DirectAdmin-File-Name: " . $data['filename']
            );

            return $data['content'];
        } else {
            return $data;
        }
    }

    /**
     * @param array $options Curl Options
     * @return mixed
     */
    protected function setAuth()
    {
        $this->options[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC;
        $this->options[CURLOPT_USERPWD] = $this->params['API_USER'] . ":" . $this->params['API_PASSWORD'];
    }

    /**
     * @param mixed $response
     * @return mixed
     */
    protected function parseResponse($response)
    {
        return $response;
    }
}
