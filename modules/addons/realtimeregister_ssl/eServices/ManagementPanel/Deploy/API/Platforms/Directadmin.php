<?php

declare(strict_types=1);

namespace MGModule\RealtimeRegisterSsl\eServices\ManagementPanel\Deploy\API\Platforms;

use GuzzleHttp\Exception\GuzzleException;
use MGModule\RealtimeRegisterSsl\eServices\ManagementPanel\Client\Client;
use MGModule\RealtimeRegisterSsl\mgLibs\exceptions\DeployException;

class Directadmin extends Client implements PlatformInterface
{
    private int $port = 2222;

    private array $uri = [
        'CMD_API_SSL' => "CMD_API_SSL"
    ];

    private string $contentType = "Content-Type: text/plain";

    public function __construct(array $params)
    {
        $params['contentType'] = $this->contentType;
        $params['API_PORT'] = $params['API_PORT'] ?? $this->port;
        $params['debug'] = 'on';
        parent::__construct($params);
    }


    public function uploadCertificate(string $domain, string $crt)
    {
        return "success";
    }

    /**
     * @return string
     * @throws GuzzleException
     */
    public function installCertificate(string $domain, string $key, string $crt, string $csr = null, string $ca = null): string
    {
        $argv = [
            'action' => "save",
            'domain' => $domain,
            'type' => "paste",
            'certificate' => $crt . "\r\n" . $key . "\r\n"
        ];
        $url = $this->uri['CMD_API_SSL'] . "?" . http_build_query($argv);

        $this->request($this->url($url), 'POST');

        if ($ca) {
            return $this->installCaBundle($domain, $ca);
        }

        return "Success";
    }

    /**
     * @throws GuzzleException
     */
    private function installCaBundle(string $domain, string $ca) : string {
        $argv = [
            'action' => "save",
            'domain' => $domain,
            'type' => "cacert",
            'active' => "yes",
            'cacert' => $ca
        ];
        $url = $this->uri['CMD_API_SSL'] . "?" . http_build_query($argv);
        $this->request($this->url($url), 'POST');

        return "Success";
    }

    /**
     * @return mixed
     */
    protected function getAuth() : array
    {
        return [$this->args['API_USER'], $this->args['API_PASSWORD']];
    }

    protected function getBaseUrl()
    {
        return $this->args['API_URL']
            . ':'
            . $this->args['API_PORT']
            . '/';
    }

    /**
     * @param mixed $response
     * @return mixed
     * @throws DeployException
     */
    protected function parseResponse($response) : array
    {
        $result = [];
        foreach (explode("&", urldecode($response)) as $responsePart) {
            $keyValuePair = explode("=", $responsePart);
            if ($keyValuePair[0] != "") {
                $result[$keyValuePair[0]] = $keyValuePair[1];
            }
        }

        if (isset($output['error']) && $output['error'] == 1) {
            throw new DeployException($output['text'] . "\n" . $output['details']);
        }

        return $result;
    }
}
