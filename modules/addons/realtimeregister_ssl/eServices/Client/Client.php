<?php

declare(strict_types=1);

namespace MGModule\RealtimeRegisterSsl\eServices\Client;

use MGModule\RealtimeRegisterSsl\eServices\Exceptions\DefaultException;

/**
 * Class Client
 */
class Client extends AbstractClient
{
    private \GuzzleHttp\Client $client;

    /**
     * Client constructor.
     * @param array $args
     */
    public function __construct(array $args = [])
    {
        $this->args = $args;

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
            ]
        ]);
    }

    /**
     * @throws DefaultException
     */
    public function paginate($url, $limit = 20, $options = [])
    {
        if ($limit > 100) {
            //trow error max 100 per page
            throw new DefaultException('Limit can not be higher then 100');
        }
        $page = 1;
        if (!empty($_REQUEST['page']) && is_numeric($_REQUEST['page']) && $_REQUEST['page'] > 0) {
            $page = (int)$_REQUEST['page'];
        }

        $options['query']['limit'] = $limit;
        $options['query']['offset'] = ($page - 1) * $limit;

        $response = $this->request($url, 'GET', $options);

        $response['last_page'] = $lastPage = ceil($response['total'] / $limit);
        $response['current_page'] = $page;

        if ($page > $lastPage && $lastPage > 0) {
            throw new DefaultException('The current page could not be highter then ' . $lastPage);
        }

        return $response;
    }

    /**
     * @param $url
     * @param string $type
     * @param array $options
     * @return mixed
     */
    public function request($url, $type = 'GET', $options = [])
    {
        $options['headers'] = [
            'User-Agent' => $this->getUserAgent(),
            'Authorization' => 'Basic ' . base64_encode(implode(':', $this->getAuth())),
        ];
        $response = $request = $this->client->request(strtoupper($type), $url, $options);

        if ($this->args['debug']) {
            new Debug($this->getBaseUrl() . $url, $type, $request, $response, json_encode($options));
        }

        try {
            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            return json_decode([], true);
        }
    }
}
