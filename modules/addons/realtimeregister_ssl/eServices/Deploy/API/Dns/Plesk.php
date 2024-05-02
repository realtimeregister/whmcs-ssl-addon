<?php

declare(strict_types=1);

namespace MGModule\RealtimeRegisterSsl\eServices\Deploy\API\Dns;

use MGModule\RealtimeRegisterSsl\eServices\Deploy\API\Client;
use MGModule\RealtimeRegisterSsl\mgLibs\exceptions\DNSException;

class Plesk extends Client implements PlatformInterface
{
    private int $port = 8443;

    private string $contentType = "Content-Type: text/xml";

    public function __construct(array $params)
    {
        $params['contentType'] = $this->contentType;
        $params['API_PORT'] = isset($params['API_PORT']) ? $params['API_PORT'] : $this->port;
        $params['API_URL'] = sprintf("%s/enterprise/control/agent.php", $params['API_URL']);
        $params['debug'] = 'on';
        parent::__construct($params);
    }

    /**
     * @return string
     * @throws DNSException
     */
    public function getSiteId(string $domain)
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

        try {
            $response = $this->request('POST', $this->packet($packet));
        } catch(\Exception $ex) {
            throw $ex;
        }

        if (isset($response->site->get->result->status) && (string)$response->site->get->result->status == 'error') {
            throw new DNSException((string)$response->site->get->result->errtext);
        }

        return (string)$response->site->get->result->id;
    }

    /**
     * @throws DNSException
     */
    public function createDNSRecord(string $domain, string $name, string $value, string $type): string
    {
        $siteId = $this->getSiteId($domain);
        if ($type == "CNAME") {
            $name = substr($name, 0, -(strlen($domain) + 1));
        }

        $packet = [
            'dns' => [
                'add_rec' => [
                    'site-id' => $siteId,
                    'type' => $type,
                    'host' => $name,
                    'value' => $value,
                ],
            ],
        ];

        try {
            $response = $this->request('POST', $this->packet($packet));
        } catch(\Exception $ex) {
            throw new DNSException($ex->getMessage());
        }

        if (
            isset($response->dns->add_rec->result->status) && (string)$response->dns->add_rec->result->status == 'error'
        ) {
            throw new DNSException((string)$response->dns->add_rec->result->errtext);
        }

        return $response->dns->add_rec->result->ok;
    }

    /**
     * @throws DNSException
     */
    public function getDNSRecord(string $domain): array
    {
        $records = [];
        $siteId = $this->getSiteId($domain);

        $packet = [
            'dns' => [
                'get_rec' => [
                    'filter' => [
                        'site-id' => $siteId,
                    ]
                ],
            ],
        ];
        try {
            $response = $this->request('POST', $this->packet($packet));
        } catch(\Exception $ex) {
            throw new DNSException($ex->getMessage());
        }

        foreach($response->dns->get_rec->result as $result) {
            $name = (substr($result->data->host, -1) == ".") ? substr($result->data->host, 0,-1)
                : $result->data->host;
            $content =  (substr($result->data->value, -1) == ".")
                ? substr($result->data->value, 0,-1) : $result->data->value;
            $records[] = [
                'type'    => (string) $result->data->type,
                'name'    => $name,
                'content' => $content
            ];

        }

        return $records;
    }

    private function toXML(\SimpleXMLElement &$xml, array $data = [])
    {
        foreach($data as $key => $value) {
            if (is_array($value)) {
                $node = $xml->addChild($key);
                $this->toXML($node, $value);
            } else {
                $xml->addChild($key, $value);
            }
        }
    }

    private function packet(array $data = [])
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8" ?><packet></packet>');
        $this->toXML($xml, $data);

        return $xml->asXML();
    }

    protected function setAuth(): void
    {
        array_unshift($this->options[CURLOPT_HTTPHEADER], sprintf("HTTP_AUTH_LOGIN: %s", $this->params['API_USER']),
            sprintf("HTTP_AUTH_PASSWD: %s", $this->params['API_PASSWORD']), "HTTP_PRETTY_PRINT: TRUE");
    }

    /**
     * @param mixed $response
     * @return mixed
     * @throws DNSException
     */
    protected function parseResponse($response)
    {
        $xml = new \SimpleXMLElement($response);
        if (isset($xml->status) && (string)$xml->status == 'error') {
            throw new DNSException((string)$xml->errtext);
        }

        if (isset($xml->system->status) && (string)$xml->system->status == 'error') {
            throw new DNSException((string)$xml->system->errtext);
        }

        return $xml;
    }
}
