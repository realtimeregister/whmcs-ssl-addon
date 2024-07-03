<?php

declare(strict_types=1);

namespace MGModule\RealtimeRegisterSsl\eServices\ManagementPanel\Client;

use GuzzleHttp\Exception\GuzzleException;
use MGModule\RealtimeRegisterSsl\eServices\ManagementPanel\Api\Panel\Client\Debug;

/**
 * Class Client
 */
class Client extends AbstractClient
{
    /**
     * @var \GuzzleHttp\Client
     */
    protected $client;

    /**
     * Client constructor.
     * @param array $args
     */
    public function __construct(array $args = [])
    {
        $this->args = $args;
        $this->version = "2.0.0";

        $this->client = new \GuzzleHttp\Client([
            'base_uri' => $this->getBaseUrl(),
            'auth' => $this->getAuth(),
            'defaults' => [
                'verify' => !$this->ignoreSslVerify(),
                'headers' => [
                    'User-Agent' => $this->getUserAgent()
                ]
            ],
            'headers' => [
                'User-Agent' => $this->getUserAgent()
            ],
            'verify' => false
        ]);
    }


    protected function parseResponse(string $response) {
        return json_decode($response, true);
    }

    /**
     * @return mixed
     * @throws GuzzleException
     */
    public function request(string $url, string $type = 'GET', array $options = [])
    {
        if (!array_key_exists('headers', $options)) {
            $options['headers'] = [];
        }

        $options['headers'] = array_merge(
            [
                'User-Agent' => $this->getUserAgent(),
                'Authorization' => 'Basic ' . base64_encode(implode(':', $this->getAuth())),
            ],
            $options['headers']
        );

        $response = $this->client->request(strtoupper($type), $url, $options);

        if ($this->args['debug']) {
            new Debug($this->getBaseUrl() . $url, $type, $options['headers'], $response, $options);
        }

        try {
            return $this->parseResponse((string) $response->getBody());
        } catch (\Exception $e) {
            return json_decode($e->getMessage(), true);
        }
    }
}
