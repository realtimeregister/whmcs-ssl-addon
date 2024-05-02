<?php

declare(strict_types=1);

namespace MGModule\RealtimeRegisterSsl\eServices\Deploy\API\File;

use MGModule\RealtimeRegisterSsl\eServices\Deploy\API\Client;
use MGModule\RealtimeRegisterSsl\mgLibs\exceptions\FileException;

class Directadmin extends Client implements PlatformInterface
{
    private int $port = 2222;

    private array $uri = [
        'CMD_API_FILE_MANAGER' => "/CMD_API_FILE_MANAGER",
        'CMD_FILE_MANAGER'     => "/CMD_FILE_MANAGER",
    ];

    private string $contentType = "Content-Type: text/plain";

    private int $i = 0;

    public function __construct(array $params)
    {
        $params['contentType'] = $this->contentType;
        $params['API_PORT'] = $params['API_PORT'] ?? $this->port;
        $params['debug'] = 'on';
        parent::__construct($params);
    }

    /**
     * @param $file
     * @param string $dir
     * @return mixed
     * @throws FileException
     */
    public function uploadFile($file, $dir)
    {
        $argc = [
            'action'   => 'upload',
            'filename' => $file['name'],
            "content"  => $file['content'],
        ];
        $url = [
            'action' => 'upload',
            'path'   => "/domains/" . $this->params['domain'] . "/public_html/" . $dir,
        ];
        try {
            $response = $this->url([$this->uri['CMD_API_FILE_MANAGER'] . "?" . http_build_query($url)], false, true)
                ->request('POST', $argc);
        } catch (\Exception $ex) {
            throw new FileException($ex->getMessage());
        }
        parse_str($response, $output);
        if (isset($output['error']) && $output['error'] == 1) {
            if (str_contains($output['details'], "Unable to open") && $this->i == 0) {
                $this->i++;

                return $this->makeFileWithPath($file, $dir);
            } else {
                throw new FileException($output['text'] . $output['details']);
            }
        }

        if ($output['error'] == 0) {
            return "success";
        }

        return "Unknown Error";
    }


    /**
     * @throws FileException
     */
    private function makeFileWithPath($file, $dir)
    {
        try {
            $this->makePath($dir);
            $response = $this->uploadFile($file, $dir);
        } catch (\Exception $e) {
            throw new FileException($e->getMessage(), $e->getCode(), $e->getPrevious());
        }

        return $response;
    }

    /**
     * @param $dir
     * @throws FileException
     */
    private function makePath($dir)
    {
        $mainDir = "";
        $dirSplit = explode('/', $dir);
        foreach ($dirSplit as $subDir) {
            if ($subDir == "") {
                continue;
            }
            $this->makeDir($subDir, $mainDir);
            $mainDir .= ($mainDir == "" ? "" : "/") . $subDir;
        }
    }

    /**
     * @param array $name
     * @param string $dir
     * @return mixed
     * @throws FileException
     */
    public function makeDir($name, $dir)
    {
        $argc = [
            'action' => 'folder',
            'path'   => "/domains/" . $this->params['domain'] . "/public_html/" . $dir,
            'name'   => $name,
        ];
        try {
            $response = $this->url([
                $this->uri['CMD_API_FILE_MANAGER'] . "?" . http_build_query($argc),
            ], false, true)->request('POST', $argc);
        } catch (\Exception $ex) {
            throw new FileException($ex->getMessage());
        }
        parse_str($response, $output);
        if (
            isset($output['error']) && $output['error'] == 1
            && !str_contains($output['details'], "The path already exists")
        ) {
            throw new FileException($output['text'] . $output['details']);
        }

        return "success";
    }


    /**
     * @param $file
     * @param string $dir
     * @return mixed
     * @throws FileException
     */
    public function getFile($file, $dir)
    {
        $url = [
            'path' => "/domains/" . $this->params['domain'] . "/public_html/" . $dir . "/" . $file['name'],
        ];
        try {
            $response = $this->url([$this->uri['CMD_FILE_MANAGER'] . "?" . http_build_query($url)], false, true)
                ->request('GET');
        } catch (\Exception $ex) {
            throw new FileException($ex->getMessage());
        }

        if (str_contains($response, 'Error')) {
            throw new FileException($response);
        }

        return ['content' => $response];
    }

    /**
     * @param $data
     * @return string|void
     */
    protected function setData($data)
    {
        if ($data['action'] == "upload") {
            array_unshift($this->options[CURLOPT_HTTPHEADER],
                "X-DirectAdmin-File-Upload: yes",
                "X-DirectAdmin-File-Name: " . $data['filename']
            );

            return $data['content'];
        } else {
            return $data;
        }

    }

    protected function setAuth(): void
    {
        $this->options[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC;
        $this->options[CURLOPT_USERPWD] = $this->params['API_USER'] . ":" . $this->params['API_PASSWORD'];
    }

    /**
     * @param mixed $response
     * @return mixed
     * @throws FileException
     */
    protected function parseResponse($response)
    {
        return $response;
    }
}
