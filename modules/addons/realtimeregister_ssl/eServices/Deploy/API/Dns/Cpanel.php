<?php

declare(strict_types=1);

namespace MGModule\RealtimeRegisterSsl\eServices\Deploy\API\Dns;

use MGModule\RealtimeRegisterSsl\eServices\Deploy\API\Client;
use MGModule\RealtimeRegisterSsl\mgLibs\exceptions\DNSException;

class Cpanel extends Client implements PlatformInterface
{
    private int $port = 2083;

    private array $uri = [
        'ADD_ZONE_RECORD' => 'json-api/cpanel?cpanel_jsonapi_apiversion=2' .
            '&cpanel_jsonapi_module=ZoneEdit&cpanel_jsonapi_func=add_zone_record',
        'GET_ZONE_RECORDS' => 'json-api/cpanel?cpanel_jsonapi_apiversion=2' .
            '&cpanel_jsonapi_module=ZoneEdit&cpanel_jsonapi_func=fetchzone',
    ];

    private string $contentType = "Content-Type: application/x-www-form-urlencoded";

    /**
     *
     * @param array $params
     */
    public function __construct($params)
    {
        $params['contentType'] = $this->contentType;
        $params['API_PORT'] = $params['API_PORT'] ? $params['API_PORT'] : $this->port;
        $params['API_URL'] = sprintf("%s/", $params['API_URL']);
        $params['debug'] = 'on';
        parent::__construct($params);
    }

    /**
     * @throws DNSException
     */
    public function createDNSRecord(string $domain, string $name, string $value, string $type): string
    {
        $args = [
            'domain' => $domain,
            'type' => $type,
            'name' => $name,
        ];

        if($type == "A" || $type == "AAAA") {
            $args['address'] = $value;
        } elseif($type == "CNAME") {
            $argc['name'] = substr($name,  0,-(strlen($domain)+1));
            $args['cname'] = $value;
        } elseif($type == "TXT") {
            $args['txtdata'] = $value;
        }

        try {
            $response = $this->url([$this->uri['ADD_ZONE_RECORD']], false, true)->request('POST', $args);
        } catch(\Exception $ex) {
            throw new DNSException($ex->getMessage());
        }

        if(
            isset($response->cpanelresult->data[0]->result->status)
            && $response->cpanelresult->data[0]->result->status == 0
        ) {
            throw new DNSException($response->cpanelresult->data[0]->result->statusmsg);
        }

        return $response->cpanelresult->data[0]->result->statusmsg;
    }

    public function getDNSRecord(string $domain): array
    {
        $records = [];
        $args = [
            'domain' => $domain,
            'customonly' => 1,
        ];

        try {
            $response = $this->url([ $this->uri['GET_ZONE_RECORDS']], false, true)->request('POST', $args);
        } catch(\Exception $ex) {
            throw new DNSException($ex->getMessage());
        }

        if(isset($response->cpanelresult->data[0]->status) && $response->cpanelresult->data[0]->status == 0) {
            throw new DNSException($response->cpanelresult->data[0]->statusmsg);
        }

        foreach($response->cpanelresult->data[0]->record as $record) {
            $name =  (substr($record->name, -1) == ".") ? substr($record->name, 0,-1) : $record->name;
            $records[] = [
                'type'    => $record->type,
                'name'    => $name,
                'content' => $record->record
            ];

        }
        return $records;
    }

    protected function setAuth(): void
    {
        array_unshift($this->options[CURLOPT_HTTPHEADER], sprintf("Authorization: Basic %s",
            base64_encode($this->params['API_USER'] . ":" . $this->params['API_PASSWORD'])));
    }

    /**
     * @param mixed $response
     * @return mixed
     * @throws DNSException
     */
    protected function parseResponse($response)
    {
        $result = json_decode($response);
        if(isset($result->cpanelresult) && isset($result->cpanelresult->error)) {
            if(isset($result->cpanelresult->data->reason)) {
                throw new DNSException($result->cpanelresult->data->reason);
            } else {
                throw new DNSException($result->cpanelresult->error);
            }
        }

        return $result;
    }
}
