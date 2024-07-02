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
        'CMD_API_SSL' => "CMD_API_SSL",
        'CMD_SSL' => "CMD_SSL",
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

    public function uploadCertificate(string $domain, string $crt)
    {
        return "";
    }

    /**
     * @return string
     * @throws DeployException
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
        $output = $this->request($this->url($url), 'POST');

        if (isset($output['error']) && $output['error'] == 1) {
            throw new DeployException($output['text'] . "\n" . $output['details']);
        }

        if ($output['error'] == 0) {
            if ($ca) {
                return $this->installCaBundle($domain, $ca);
            }
            return 'Success';
        }

        return "Unknown Error";
    }

    /**
     * @throws DeployException
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
        $output = $this->request($this->url($url), 'POST');

        if (isset($output['error']) && $output['error'] == 1) {
            throw new DeployException($output['text'] . "\n" . $output['details']);
        }

        if ($output['error'] == 0) {
            return "Success";
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
     */
    protected function parseResponse($response)
    {
        $result = [];
        foreach (explode("&", urldecode($response)) as $responsePart) {
            $keyValuePair = explode("=", $responsePart);
            if ($keyValuePair[0] != "") {
                $result[$keyValuePair[0]] = $keyValuePair[1];
            }
        }
        return $result;
    }

    public function getKey(string $domain, string $id): string
    {
        return "";
    }
}
