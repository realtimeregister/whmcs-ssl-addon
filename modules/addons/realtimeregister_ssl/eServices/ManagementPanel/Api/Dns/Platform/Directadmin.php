<?php

declare(strict_types=1);

namespace AddonModule\RealtimeRegisterSsl\eServices\ManagementPanel\Api\Dns\Platform;

use AddonModule\RealtimeRegisterSsl\eServices\ManagementPanel\Api\Dns\Client;
use AddonModule\RealtimeRegisterSsl\addonLibs\exceptions\DNSException;

class Directadmin extends Client implements PlatformInterface
{
    private int $port = 2222;

    private array $uri = [
        'CMD_API_DNS_CONTROL' => "/CMD_API_DNS_CONTROL",
    ];

    private string $contentType = "Content-Type: application/x-www-form-urlencoded";

    public function __construct(array $params)
    {
        $params['API_PORT'] = $params['API_PORT'] ?? $this->port;
        $params['contentType'] = $this->contentType;
        $params['debug'] = 'on';
        parent::__construct($params);
    }

    /**
     * @throws DNSException
     */
    public function createDNSRecord(string $domain, string $name, string $value, string $type): string
    {
        try {
            $response = $this->url([
                $this->uri['CMD_API_DNS_CONTROL']
            ], false, true)->request('POST', [
                'domain' => $domain,
                'action' => 'add',
                'type' => $type,
                'name' => sprintf("%s.", $name),
                'value' => $value,
            ]);
        } catch (DNSException $ex) {
            throw new DNSException($ex->getMessage());
        }

        parse_str($response, $output);

        if (!isset($output['error'])) {
            throw new DNSException("Something went wrong.");
        }

        if ($output['error'] == 1) {
            unset($output['error']);
            throw new DNSException(join(". ", $output));
        }

        return $response;
    }

    /**
     * @return mixed
     * @throws DNSException
     */
    public function getDNSRecord(string $domain)
    {
        try {
            $response = $this->url([
                $this->uri['CMD_API_DNS_CONTROL']
            ], false, true)->request('POST', [
                'domain' => $domain,
                'urlencoded' => 'yes'
            ]);
        } catch (DNSException $ex) {
            throw new DNSException($ex->getMessage());
        }

        parse_str($response, $output);

        if ($output['error'] == 1) {
            unset($output['error']);
            throw new DNSException(join(". ", $output));
        }

        $records = $this->parseRecords($response);
        if (empty($records)) {
            throw new DNSException("Something went wrong.");
        }

        return $records;
    }

    /**
     * @return mixed
     */
    protected function setAuth()
    {
        $this->options[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC;
        $this->options[CURLOPT_USERPWD] = $this->params['API_USER'] . ":" . $this->params['API_PASSWORD'];
    }

    /**
     * @param $post
     * @return string
     */
    protected function setData($post)
    {
        return http_build_query($post);
    }

    /**
     * @param mixed $response
     * @return mixed
     */
    protected function parseResponse(string $response): string
    {
        return $response;
    }

    private function parseRecords($response)
    {
        $split = explode("\n", $response);
        $output = [];

        foreach ($split as $line) {
            $pos = strpos($line, "=");
            $type = substr($line, 0, $pos);
            $line = substr($line, $pos + 1);
            $loutputs = explode("&", urldecode($line));
            foreach ($loutputs as $loutput) {
                $split1 = explode("=", $loutput);
                if (isset($type) && isset($split1[0]) && isset($split1[1])) {
                    $output[] = [
                        'type' => $type,
                        'name' => (str_ends_with($split1[0], ".")) ? substr($split1[0], 0, -1) : $split1[0],
                        'content' => (str_ends_with($split1[1], ".")) ? substr($split1[1], 0, -1) : $split1[1]
                    ];
                }
            }
            unset($loutput);
        }

        return $output;
    }
}
