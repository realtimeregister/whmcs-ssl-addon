<?php

declare(strict_types=1);

namespace MGModule\RealtimeRegisterSsl\eServices\ManagementPanel\Api\File\Platforms;

use MGModule\RealtimeRegisterSsl\eServices\ManagementPanel\Api\File\Client;
use MGModule\RealtimeRegisterSsl\eServices\ManagementPanel\Api\File\Exceptions\FileException;
use MGModule\RealtimeRegisterSsl\mgLibs\exceptions\DNSException;

class Plesk extends Client implements PlatformInterface
{
    private int $port = 8443;

    private string $contentType = "Content-Type: text/xml";
    private $siteInformation;

    public function __construct(array $params)
    {
        $params['contentType'] = $this->contentType;
        $params['API_PORT'] = $params['API_PORT'] ?? $this->port;
        $params['API_URL'] = sprintf("%s/enterprise/control/agent.php", $params['API_URL']);
        $params['debug'] = 'on';
        parent::__construct($params);
    }

    public function uploadFile($file, $dir)
    {
        $path = $this->getHostingProperty($this->params['domain'], 'www_root');

        $packet = [
            'extension' => [
                'call' => [
                    'realtime-register-ssl' => [
                        'install_file_validation' => [
                            'file' => [
                                'filename' => $path . $file['dir'] . '/' . $file['name'],
                                'content' => $file['content']
                            ],
                        ]
                    ],
                ],
            ],
        ];

        $response = $this->request('POST', $this->packet($packet));
        dd($response);
    }

    /**
     * @param array $file [name,content]
     * @param string $dir
     * @return mixed
     * @throws FileException
     */
    public function uploadFile_tmp($file, $dir)
    {
        // File to upload
        $fileName = ltrim($dir . '/' . $file['name'], '/');
        $fileContents = base64_encode($file['content']);

        $siteId = $this->getSiteId($this->params['domain']);
dd($siteId);
        $path = $this->getHostingProperty($this->params['domain'], 'www_root');

        $headers = array(
            "HTTP_AUTH_LOGIN: " . $this->params['API_USER'],
            "HTTP_AUTH_PASSWD: " . $this->params['API_PASSWORD'],
            "HTTP_PRETTY_PRINT: TRUE",
            "Content-Type: multipart/form-data;",
        );

        // Initialize the curl engine
        $ch = curl_init();

        // Set the curl options
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        // this line makes it work under https
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // Set the URL to be processed
        curl_setopt($ch, CURLOPT_URL, 'https://plesk.yoursrs.com:8443/enterprise/control/agent.php');

        $filename = '/tmp/random.txt';
        curl_setopt($ch, CURLOPT_POSTFIELDS,
            ['/tmp/blaat.txt'=>"@$filename"]
        );
//dd(curl_getinfo($ch));
        $result = curl_exec($ch);

        if (curl_errno($ch)) {
            echo "\n\n-------------------------\n" .
                "cURL error number:" .
                curl_errno($ch);
            echo "\n\ncURL error:" . curl_error($ch);
        }
dd($result);
        curl_close($ch);

//fclose($fp);

        return;
//
//        // Prepare the XML request

//
//
//        try {
//            $response = $this->request('POST', $xmlRequest);
//        } catch (FileException $ex) {
//            dd($ex);
//            if (strpos($ex->getMessage(), "The file “” does not exist") !== false) {
//                return $this->makeFileWithPath($file, $dir);
//            } else {
//                throw new FileException($ex->getMessage());
//            }
//        }
//        dd($response);
//
//        if (isset($response->cpanelresult->error)) {
//            throw new FileException($response->cpanelresult->error);
//        }
//
//        return "success";
    }

    /**
     *
     * @throws DNSException
     */
    public function getSiteId(string $domain): string
    {
        if (!$this->siteInformation) {
        $this->getSiteInformation($domain);
        }
        return (string)$this->siteInformation->id;
    }

    private function getHostingProperty(string $domain, string $property)
    {
        if (!$this->siteInformation) {
            $this->getSiteInformation($domain);
        }

        foreach ($this->siteInformation->data->hosting->vrt_hst->property as $val) {
            if ($val->name == $property) {
                return (string)$val->value;
            }
        }
    }

    private function getSiteInformation(string $domain)
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

        $response = $this->request('POST', $this->packet($packet));

        if (isset($response->site->get->result->status) && (string)$response->site->get->result->status == 'error') {
            throw new DNSException((string)$response->site->get->result->errtext);
        }

        $this->siteInformation = $response->site->get->result;

        return $response->site->get->result;
    }


    /**
     *
     * @return string
     */
    private function packet(array $data = [])
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8" ?><packet></packet>');
        $this->toXML($xml, $data);

        return $xml->asXML();
    }

    private function toXML(\SimpleXMLElement &$xml, array $data = [])
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $node = $xml->addChild($key);
                $this->toXML($node, $value);
            } else {
                $xml->addChild($key, $value);
            }
        }
    }

    /**
     */
    protected function setAuth()
    {
        array_unshift($this->options[CURLOPT_HTTPHEADER], sprintf("HTTP_AUTH_LOGIN: %s", $this->params['API_USER']),
            sprintf("HTTP_AUTH_PASSWD: %s", $this->params['API_PASSWORD']), "HTTP_PRETTY_PRINT: TRUE");
    }

    /**
     * @param mixed $response
     * @return mixed
     * @throws DNSException
     */
    protected function parseResponse($response)
    {
        dump($response);
        $xml = new \SimpleXMLElement($response);
        if (isset($xml->status) && (string)$xml->status == 'error') {
            throw new DNSException((string)$xml->errtext);
        }

        if (isset($xml->system->status) && (string)$xml->system->status == 'error') {
            throw new DNSException((string)$xml->system->errtext);
        }

        return $xml;
    }

    public function getFile(string $file, string $dir)
    {
        // TODO: Implement getFile() method.
    }
}