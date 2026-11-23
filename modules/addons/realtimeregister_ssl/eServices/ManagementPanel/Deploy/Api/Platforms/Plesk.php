<?php

declare(strict_types=1);

namespace AddonModule\RealtimeRegisterSsl\eServices\ManagementPanel\Deploy\Api\Platforms;

use AddonModule\RealtimeRegisterSsl\addonLibs\exceptions\DeployException;
use AddonModule\RealtimeRegisterSsl\eServices\ManagementPanel\Client\Client;
use GuzzleHttp\Exception\GuzzleException;
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
     * @throws GuzzleException
     */
    public function installCertificate($domain, $key, $crt, $csr = null, $ca = null): string
    {
        $name = $domain . "_realtimeregister_ssl_autodeploy";
        $normalizedDomain = $this->normalizeDomain($domain);

        $this->storeCertificate($normalizedDomain, $name, [
            'csr' => $csr,
            'pvt' => $key,
            'cert' => $crt,
            'ca' => $ca,
        ]);

        // We can now enable the certificate to the domain:
        $siteIds = [$this->getSiteId($normalizedDomain)];

        if ($this->isWildCard($domain)) {
            array_push($siteIds, ...$this->getAllSubDomains($normalizedDomain));
        }

        // Try every site before failing, so one broken subdomain doesn't keep the others on the old certificate
        $errors = [];
        foreach ($siteIds as $siteId) {
            try {
                $this->assignCertificate($siteId, $name);
            } catch (DeployException $e) {
                $errors[] = sprintf('site %s: %s', $siteId, $e->getMessage());
            }
        }

        if ($errors) {
            throw new DeployException('Unable to assign the certificate to ' . implode('; ', $errors));
        }

        return 'success';
    }

    /**
     * Replaces our own certificate when an earlier version of it is in the repository, otherwise adds it
     *
     * @throws DeployException
     * @throws GuzzleException
     */
    private function storeCertificate(string $domain, string $name, array $content): void
    {
        $updateError = null;

        if (in_array($name, $this->getCertificateNames($domain), true)) {
            try {
                $response = $this->sendRequest([
                    'certificate' => [
                        'update' => [
                            'name' => $name,
                            'site' => $domain,
                            'content' => $content,
                        ],
                    ],
                ]);
                $this->assertSuccess($response, 'certificate/update');
                return;
            } catch (DeployException $e) {
                // The update operation is undocumented and may be unavailable, fall back to a regular install
                $updateError = $e->getMessage();
            }
        }



        try {
            $response = $this->sendRequest([
                'certificate' => [
                    'install' => [
                        'name' => $name,
                        'webspace' => $domain,
                        'content' => $content,
                    ],
                ],
            ]);
            $this->assertSuccess($response, 'certificate/install');
        } catch (DeployException $e) {
            if ($updateError === null) {
                throw $e;
            }
            throw new DeployException(
                sprintf('Certificate update failed: %s; install failed: %s', $updateError, $e->getMessage())
            );
        }
    }

    /**
     * @return string[]
     * @throws DeployException
     * @throws GuzzleException
     */
    private function getCertificateNames(string $domain): array
    {
        $response = $this->sendRequest([
            'certificate' => [
                'get-pool' => [
                    'filter' => [
                        'domain-name' => $domain,
                    ],
                ],
            ],
        ]);

        return array_map(
            'strval',
            $response->xpath('certificate/get-pool/result[status="ok"]/certificates/certificate/name') ?: []
        );
    }

    /**
     * @throws DeployException
     * @throws GuzzleException
     */
    private function assignCertificate(string $siteId, string $name): void
    {
        $response = $this->sendRequest([
            'site' => [
                'set' => [
                    'filter' => [
                        'id' => $siteId
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
        ]);
        $this->assertSuccess($response, 'site/set');
    }

    /**
     * Plesk reports a failed operation in its result node, not at packet level
     *
     * @throws DeployException
     */
    private function assertSuccess(SimpleXMLElement $response, string $operation): void
    {
        $results = $response->xpath($operation . '/result') ?: [];
        if (!$results) {
            throw new DeployException(sprintf('Unexpected response from Plesk for %s', $operation));
        }

        foreach ($results as $result) {
            if ((string)$result->status !== 'ok') {
                throw new DeployException(
                    (string)$result->errtext ?: sprintf('Plesk returned an error for %s', $operation)
                );
            }
        }
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

        $response = $this->sendRequest($packet);

        if (isset($response->site->get->result->status) && (string)$response->site->get->result->status == 'error') {
            throw new DeployException((string)$response->site->get->result->errtext);
        }

        return (string)$response->site->get->result->id;
    }

    /**
     * @throws DeployException
     */
    protected function getAllSubDomains(string $domain) : array {
        $packet = [
            'site' => [
                'get' => [
                    'filter' => [
                        'parent-site-id' => $this->getSiteId($domain),
                    ],
                    'dataset' => [
                        'hosting' => [],
                    ],
                ],
            ],
        ];

        $response = $this->sendRequest($packet);
        if (isset($response->site->get->result->status) && (string)$response->site->get->result->status == 'error') {
            throw new DeployException((string)$response->site->get->result->errtext);
        }

        $siteIds = [];
        foreach ($response->xpath('site/get/result') ?: [] as $result) {
            $siteId = (string)$result->id;
            // Without any subdomains, the result has no id
            if ($siteId === '') {
                continue;
            }
            // A certificate can't be assigned to subdomains without web hosting
            if (isset($result->data->hosting) && !isset($result->data->hosting->vrt_hst)) {
                continue;
            }
            $siteIds[] = $siteId;
        }
        return $siteIds;
    }

    private function sendRequest(array $packet) : SimpleXMLElement {
        return $this->request($this->getBaseUrl(), 'POST', [
                'body' => $this->packet($packet),
                'headers' => [
                    "HTTP_AUTH_LOGIN" => $this->args['API_USER'],
                    "HTTP_AUTH_PASSWD" => $this->args['API_PASSWORD']
                ]
            ]
        );
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
