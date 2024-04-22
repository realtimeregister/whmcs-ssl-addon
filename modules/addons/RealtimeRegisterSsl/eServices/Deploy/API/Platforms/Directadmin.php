<?php

namespace MGModule\RealtimeRegisterSsl\eServices\Deploy\API\Platforms;

use MGModule\RealtimeRegisterSsl\eServices\Deploy\API\Client;
use \MGModule\RealtimeRegisterSsl\mgLibs\exceptions\DeployException;

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
        $params['API_PORT'] = isset($params['API_PORT']) ?? $this->port;
        $params['debug'] = 'on';
        parent::__construct($params);
    }

    /**
     * @param string $domain
     * @param array $csrData
     * @return array [key, csr]
     * @throws DeployException
     */
    public function genKeyCsr(string $domain, array $csrData)
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
        try {
            $response = $this->url([$url], false, true)
                ->request('POST');
        } catch (DeployException $ex) {
            throw new DeployException($ex->getMessage());
        }
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

    public function getKey(string $domain, $id = null)
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

    /**
     * @param string $domain
     * @param string $crt
     * @throws DeployException
     */
    public function uploadCertificate(string $domain, $crt)
    {
        return true;
    }

    /**
     * @param string $domain
     * @param string $key
     * @param string $csr
     * @param string $ca
     * @return string
     * @throws DeployException
     */
    public function installCertificate(string $domain, $key, array $crt, $csr = null, $ca = null)
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
     * @param $post
     * @return string|void|array
     */
    protected function setData(array $post)
    {
        if ($post['action'] == "upload") {
            array_unshift(
                $this->options[CURLOPT_HTTPHEADER],
                "X-DirectAdmin-File-Upload: yes",
                "X-DirectAdmin-File-Name: " . $post['filename']
            );

            return $post['content'];
        } else {
            return $post;
        }
    }

    /**
     * @param array $options Curl Options
     * @return mixed
     */
    protected function setAuth(): void
    {
        $this->options[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC;
        $this->options[CURLOPT_USERPWD] = $this->params['API_USER'] . ":" . $this->params['API_PASSWORD'];
    }

    /**
     * @param mixed $response
     * @return mixed
     * @throws DeployException
     */
    protected function parseResponse($response)
    {
        return $response;
    }
}
