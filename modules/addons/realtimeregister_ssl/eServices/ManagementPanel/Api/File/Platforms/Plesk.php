<?php

declare(strict_types=1);

namespace AddonModule\RealtimeRegisterSsl\eServices\ManagementPanel\Api\File\Platforms;

use AddonModule\RealtimeRegisterSsl\eServices\ManagementPanel\Api\File\Client;
use AddonModule\RealtimeRegisterSsl\eServices\ManagementPanel\Api\File\Exceptions\FileException;
use AddonModule\RealtimeRegisterSsl\mgLibs\exceptions\DNSException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

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
                                'domain' => $this->params['domain'],
                                'filename' => $path . $file['dir'] . '/' . $file['name'],
                                'content' => $file['content']
                            ],
                        ]
                    ],
                ],
            ],
        ];

        $this->request('POST', $this->packet($packet));

        $this->detectAndUpdateExtension();

        $this->request();
    }

    public function detectAndUpdateExtension()
    {
        $ch = curl_init();

        $urlParts = parse_url($this->params['API_URL']);

        curl_setopt($ch, CURLOPT_URL, $urlParts['scheme'] . '://' . $urlParts['host'] . '/api/v2/extensions');

        curl_setopt($ch, CURLOPT_USERPWD, $this->params['API_USER'] . ":" . $this->params['API_PASSWORD']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $res = curl_exec($ch);

        $result = json_decode($res);

        $found = false;
        foreach ($result as $r) {
            if ($r->id === 'realtime-register-ssl' && $r->active === true) {
                $xml = simplexml_load_file(
                    __DIR__ . '/../../../Deploy/API/Platforms/Module/ext-realtime-register-ssl-file-upload-helper/meta.xml'
                );

                if ($r->version === (string)$xml->version && $r->release === (string)$xml->release) {
                    $found = true;
                }
            }
        }

        if (!$found) {
            $this->removeExtension(); // try, for example, if there is an old version installed
            $this->uploadExtension();
        }
    }

    private function removeExtension()
    {
        $ch = curl_init();

        $urlParts = parse_url($this->params['API_URL']);

        curl_setopt(
            $ch,
            CURLOPT_URL,
            $urlParts['scheme'] . '://' . $urlParts['host'] . '/api/v2/extensions/realtime-register-ssl'
        );

        curl_setopt($ch, CURLOPT_USERPWD, $this->params['API_USER'] . ":" . $this->params['API_PASSWORD']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");

        curl_exec($ch);
    }

    /**
     * @return mixed
     * @throws FileException
     */
    public function uploadExtension(): string
    {
        $fileName = $this->createExtensionZipfile();

        $url = parse_url($this->params['API_URL']);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url['scheme'] . '://' . $url['host'] . ':8443' . $url['path']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'HTTP_AUTH_LOGIN: ' . $this->params['API_USER'],
            'HTTP_AUTH_PASSWD: ' . $this->params['API_PASSWORD'],
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, [
            'myfile' => new \CURLFile($fileName),
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);

        $xml = simplexml_load_string($response);

        curl_close($ch);
        if ($xml && (string)$xml->upload->result->status === 'ok') {
            $this->installExtension((string)$xml->upload->result->file);
        }
        throw new FileException('Extension uploaded not successful');
    }

    private function installExtension(string $location)
    {
        $ch = curl_init();

        $urlParts = parse_url($this->params['API_URL']);

        curl_setopt($ch, CURLOPT_URL, $urlParts['scheme'] . '://' . $urlParts['host'] . '/api/v2/extensions');

        curl_setopt($ch, CURLOPT_USERPWD, $this->params['API_USER'] . ":" . $this->params['API_PASSWORD']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'HTTP_AUTH_LOGIN: ' . $this->params['API_USER'],
            'HTTP_AUTH_PASSWD: ' . $this->params['API_PASSWORD'],
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'file' => $location
        ]));
        $res = curl_exec($ch);

        $result = json_decode($res,true);

        if ($result['status'] === 'success') {
            return true;
        }

        return false;
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
        array_unshift(
            $this->options[CURLOPT_HTTPHEADER],
            sprintf("HTTP_AUTH_LOGIN: %s", $this->params['API_USER']),
            sprintf("HTTP_AUTH_PASSWD: %s", $this->params['API_PASSWORD']),
            "HTTP_PRETTY_PRINT: TRUE"
        );
    }

    /**
     * @param mixed $response
     * @return mixed
     * @throws DNSException
     */
    protected function parseResponse($response)
    {
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


    private function createExtensionZipfile(): string
    {
        // First create the file
        $tmpFile = sys_get_temp_dir() . '/' . uniqid();
        $moduleDir = realpath(__DIR__ . '/../../../Deploy/API/Platforms/Module/ext-realtime-register-ssl-file-upload-helper');

        $zip = new \ZipArchive();

        $zip->open($tmpFile, \ZipArchive::CREATE);

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($moduleDir),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (!$file->isDir()) {
                // Get real and relative path for current file
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($moduleDir) + 1);

                // Add current file to archive
                $zip->addFile($filePath, $relativePath);
            }
        }

        $zip->close();

        return $tmpFile;
    }
}