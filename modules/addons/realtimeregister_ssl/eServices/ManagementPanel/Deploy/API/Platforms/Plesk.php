<?php

declare(strict_types=1);

namespace MGModule\RealtimeRegisterSsl\eServices\ManagementPanel\Deploy\Api\Platforms;

use MGModule\RealtimeRegisterSsl\eServices\ManagementPanel\Client\Client;
use MGModule\RealtimeRegisterSsl\mgLibs\exceptions\DeployException;
use SimpleXMLElement;

class Plesk extends Client implements PlatformInterface
{
    private int $port = 8443;

    private string $contentType = "Content-Type: text/xml";

    public function __construct(array $params)
    {
        $params['contentType'] = $this->contentType;
        $params['API_PORT'] = $params['API_PORT'] ?? $this->port;
        $params['API_URL'] = sprintf("%s/enterprise/control/agent.php", $params['API_URL']);
        $params['debug'] = 'on';
        parent::__construct($params);
    }

    /**
     * @return array [csr, key]
     * @throws DeployException
     */
    public function genKeyCsr(string $domain, array $csrData): array
    {
        $packet = [
            'certificate' => [
                'generate' => [
                    'info' => [
                        'bits' => 2048,
                        'country' => $csrData['short_country'],
                        'state' => $csrData['state'],
                        'location' => $csrData['city'],
                        'company' => $csrData['company'],
                        'dept' => $csrData['department'],
                        'email' => $csrData['approverEmail'],
                        'name' => $domain,
                    ],
                ],
            ],
        ];

        $response = $this->request('POST', $this->packet($packet));


        if (isset($response->certificate->generate->result->status) && (string)$response->certificate->generate->result->status == 'error') {
            throw new DeployException((string)$response->certificate->generate->result->errtext);
        }

        return [
            "status" => "success",
            "csr" => $response->certificate->generate->result->csr,
            "key" => $response->certificate->generate->result->pvt,

        ];
    }

    /**
     * @param $domain
     * @param $crt
     * @return string
     */
    public function uploadCertificate($domain, $crt)
    {
        return "success";
    }

    /**
     * @param $domain
     * @param $key
     * @param $crt
     * @param $csr
     * @param $ca
     * @return string
     * @throws DeployException
     */
    public function installCertificate($domain, $key, $crt, $csr = null, $ca = null): string
    {
        $name = $domain . "_realtimeregister_ssl_autodeploy";

        $packet = [
            'certificate' => [
                'install' => [
                    'name' => $name,
                    'webspace' => $domain,
                    'content' => [
                        'csr' => $csr,
                        'pvt' => $key,
                        'cert' => $crt,
                        'ca' => $ca,
                    ],
                ],
            ],
        ];

        $response = $this->request('POST', $this->packet($packet));

        if (isset($response->certificate->install->result->status) && (string)$response->certificate->install->result->status == 'error') {
            throw new DeployException((string)$response->certificate->install->result->errtext);
        }

        return "success";
    }


    /**
     *
     * @param string $domain
     * @return string
     * @throws DeployException
     */
    public function getSiteId($domain)
    {
        $packet = [
            'site' => [
                'get' => [
                    'filter' => [
                        'name' => $domain,
                    ],
                    'dataset' => [
                        'hosting' => [],
                    ],
                ],
            ],
        ];

        $response = $this->request('POST', $this->packet($packet));

        if (isset($response->site->get->result->status) && (string)$response->site->get->result->status == 'error') {
            throw new DeployException((string)$response->site->get->result->errtext);
        }

        return (string)$response->site->get->result->id;
    }


    /**
     *
     * @param array $data
     * @param SimpleXMLElement $xml
     */
    private function toXML(array $data = [], SimpleXMLElement &$xml)
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $node = $xml->addChild($key);
                $this->toXML($value, $node);
            } else {
                $xml->addChild($key, $value);
            }
        }
    }

    /**
     *
     * @param array $data
     * @return string
     */
    private function packet(array $data = [])
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8" ?><packet></packet>');
        $this->toXML($data, $xml);

        return $xml->asXML();
    }

    /**
     */
    protected function setAuth()
    {
        array_unshift($this->options[CURLOPT_HTTPHEADER], sprintf("HTTP_AUTH_LOGIN: %s", $this->params['API_USER']),
            sprintf("HTTP_AUTH_PASSWD: %s", $this->params['API_PASSWORD']), "HTTP_PRETTY_PRINT: TRUE");
    }


    /**
     * @param mixed $response
     * @return mixed
     * @throws DeployException
     */
    protected function parseResponse($response)
    {
        $xml = new SimpleXMLElement($response);
        if (isset($xml->status) && (string)$xml->status == 'error') {
            throw new DeployException((string)$xml->errtext);
        }

        if (isset($xml->system->status) && (string)$xml->system->status == 'error') {
            throw new DeployException((string)$xml->system->errtext);
        }

        return $xml;
    }

    public function getKey(string $domain, string $id): string
    {
        return false;
    }
}
