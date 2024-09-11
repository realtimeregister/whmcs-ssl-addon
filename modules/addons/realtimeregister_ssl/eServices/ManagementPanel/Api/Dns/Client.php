<?php

namespace AddonModule\RealtimeRegisterSsl\eServices\ManagementPanel\Api\Dns;

use Exception;
use AddonModule\RealtimeRegisterSsl\addonLibs\exceptions\DNSException;

class Client
{
    protected array $params = [];
    protected array $options = [];
    protected $fp;

    private $request;
    private $response;
    private $method;
    private $postData;
    private $http_code;


    private ?string $url;

    /**
     *  User Agent
     *
     * @var string
     */
    private $ua = 'Realtime Register SSL-%s/WHMCS-%s';

    /**
     *
     * @param array $params
     */
    public function __construct(array $params = [])
    {
        $this->params = $params;
        $this->url = $this->params['API_URL'];
        $this->ua = sprintf($this->ua, $this->params['version'], $GLOBALS['CONFIG']['Version']);
    }

    public function url(array $parts = [], $reset = false, $rebuild = false): self
    {
        if ($rebuild) {
            $this->url = $this->params['API_URL'];
        }
        if (count($parts)) {
            $this->url .= join("/", $parts);
        }

        if ($reset) {
            $this->url = $this->params['API_URL'] . reset($parts);
        }

        return $this;
    }

    private function setOptions(string $method, $post, $content = null)
    {
        $this->options = [
            CURLOPT_URL => $this->url,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERAGENT => $this->ua,
            CURLOPT_HEADER => true,
            CURLINFO_HEADER_OUT => true,
            CURLOPT_HTTPHEADER => [
                $content ?: $this->params['contentType'],
            ],
        ];

        $this->setAuth();

        if (in_array($method, ['PUT', 'POST'])) {
            $data = $this->setData($post);
            if (!empty($data)) {
                $this->request['body'] = $data;
            }
            $this->options[CURLOPT_POSTFIELDS] = $data;
        }
        if (isset($this->params['API_PORT'])) {
            $this->options[CURLOPT_PORT] = $this->params['API_PORT'];
        }

        if (is_resource($this->fp)) {
            $this->options[CURLOPT_FILE] = $this->fp;
        }
    }

    /**
     * @throws Exception
     */
    protected function request(string $method = 'GET', $post, string $content = null)
    {
        $curl = curl_init();

        $this->method = $method;
        $this->request = [];
        $this->response = [];
        $this->postData = (object)$post;

        $this->setOptions($method, $post, $content);
        curl_setopt_array($curl, $this->options);

        $cResponse = curl_exec($curl);
        // Split header and body.
        $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $header = substr($cResponse, 0, $header_size);
        $body = substr($cResponse, $header_size);

        // Store all for further use.
        $this->response = [
            'header' => $header,
            'body' => $body,
        ];

        $info = curl_getinfo($curl);

        $this->http_code = $info['http_code'];
        $this->request['header'] = $info['request_header'];
        $this->request = array_reverse($this->request);

        $error = curl_error($curl);
        curl_close($curl);

        if ($this->params['debug'] == 'on') {
            $this->debug();
        }

        if ($cResponse) {
            return $this->parseResponse($this->response['body']);
        }

        if ($error) {
            throw new DNSException($error);
        }

        throw new DNSException("Something went wrong");
    }

    /**
     * Debug all the outgoing/incoming information
     */
    protected function debug()
    {
        if (empty($this->request)) {
            return;
        }

        logModuleCall("Realtime Register SSL DNS Lib", $this->method, implode(PHP_EOL, $this->request), implode(PHP_EOL, $this->response));
    }

    protected function setData($post)
    {
        $this->request['data'] = $post;
        if (!isset($this->params['contentType'])) {
            $this->params['contentType'] = 'Content-Type: application/json';
            return json_encode((object)$post, JSON_UNESCAPED_SLASHES);
        } else {
            return $post;
        }
    }

    protected function setAuth()
    {
    }

    protected function parseResponse(string $response)
    {
    }
}
