<?php

declare(strict_types=1);

namespace AddonModule\RealtimeRegisterSsl\eServices\ManagementPanel\Api\Panel\Client;

use GuzzleHttp\Exception\GuzzleException;

/**
 * Class Client
 */
class Client extends AbstractClient
{
    protected ?\GuzzleHttp\Client $client;

    public function __construct(array $args = [])
    {
        $this->args = $args;

        $this->client = new \GuzzleHttp\Client([
            'base_url' => $this->getBaseUrl(),
            'defaults' => [
                'auth' => $this->getAuth(),
                'headers' => [
                    'User-Agent' => $this->getUserAgent()
                ]
            ],
        ]);
    }

    /**
     * @return mixed
     * @throws GuzzleException
     */
    public function request(string $url, string $type = 'GET', array $options = [])
    {
        $data = $this->setOptions();

        $data = $data + $options;

        $request = $this->client->createRequest(strtoupper($type), $url, $data);
        $response = $this->client->send($request);

        return $response->getBody();
    }

    private function setOptions()
    {
        $options = $this->client->getDefaultOption();

        return [
            'auth' => $this->setAuth($options['auth']),
            'headers' => $this->setHeader($options['header']),
        ];
    }

    protected function setAuth($auth)
    {
        return $auth;
    }

    protected function setHeader($header)
    {
        return $header;
    }
}
