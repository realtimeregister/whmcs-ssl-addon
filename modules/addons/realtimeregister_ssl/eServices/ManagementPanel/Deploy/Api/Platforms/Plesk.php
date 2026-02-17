<?php

declare(strict_types=1);

namespace AddonModule\RealtimeRegisterSsl\eServices\ManagementPanel\Deploy\Api\Platforms;

use AddonModule\RealtimeRegisterSsl\eServices\ManagementPanel\Client\Client;
use AddonModule\RealtimeRegisterSsl\addonLibs\exceptions\DeployException;
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

        $response = $this->request(
            $this->getBaseUrl(),
            'POST',
            [
                'body' => $this->packet($packet),
                'headers' => [
                    "HTTP_AUTH_LOGIN" => $this->args['API_USER'],
                    "HTTP_AUTH_PASSWD" => $this->args['API_PASSWORD']
                ]
            ]
        );

        if (isset($response->certificate->install->result->status)) {
            if ((string)$response->certificate->install->result->status == 'error') {
                throw new DeployException((string)$response->certificate->install->result->errtext);
            }
            if ((string)$response->certificate->install->result->status == 'ok') {
                // We can now enable the certificate to the domain:

                $packet = [
                    'webspace' => [
                        'set' => [
                            'filter' => [
                                'id' => $this->getSiteId($domain)
                            ],
                            'values' => [
                                'hosting' => [
                                    'vrt_hst' => [
                                        'property' => [
                                            'name' => 'certificate_name',
                                            'value' => $name
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ];

                $response = $this->request(
                    $this->getBaseUrl(),
                    'POST',
                    [
                        'body' => $this->packet($packet),
                        'headers' => [
                            "HTTP_AUTH_LOGIN" => $this->args['API_USER'],
                            "HTTP_AUTH_PASSWD" => $this->args['API_PASSWORD']
                        ]
                    ]
                );

                return 'success';
            }
        }

        return 'error';
    }

    protected function getBaseUrl()
    {
        $url = parse_url($this->args['API_URL']);
        $url['port'] = $this->args['API_PORT'];
        return $url['scheme'] . '://' . $url['host'] . ':' . $url['port'] . $url['path'];
    }

    protected function getAuth(): array
    {
        return [$this->args['API_USER'], $this->args['API_PASSWORD']];
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

        $response = $this->request($this->getBaseUrl(), 'POST', [
                'body' => $this->packet($packet),
                'headers' => [
                    "HTTP_AUTH_LOGIN" => $this->args['API_USER'],
                    "HTTP_AUTH_PASSWD" => $this->args['API_PASSWORD']
                ]
            ]
        );

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
}
