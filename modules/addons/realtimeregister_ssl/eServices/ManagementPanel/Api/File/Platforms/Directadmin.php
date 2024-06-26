<?php

namespace MGModule\RealtimeRegisterSsl\eServices\ManagementPanel\Api\File\Platforms;

use MGModule\RealtimeRegisterSsl\eServices\ManagementPanel\Api\File\Client;
use MGModule\RealtimeRegisterSsl\eServices\ManagementPanel\Api\File\Exceptions\FileException;

class Directadmin extends Client implements PlatformInterface
{
    private int $port = 2222;

    private array $uri = [
        'CMD_API_FILE_MANAGER' => "/CMD_API_FILE_MANAGER",
        'CMD_FILE_MANAGER' => "/CMD_FILE_MANAGER",
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
     * @return mixed
     * @throws FileException
     */
    public function uploadFile(array $file, string $dir)
    {
        $argc = [
            'action' => 'upload',
            'filename' => $file['name'],
            "content" => $file['content'],
        ];
        $url = [
            'action' => 'upload',
            'path' => "/domains/" . $this->params['domain'] . "/public_html/" . $dir,
        ];
        try {
            $response = $this->url([$this->uri['CMD_API_FILE_MANAGER'] . "?" . http_build_query($url)], false, true)
                ->request('POST', $argc);
        } catch (FileException $ex) {
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


    private function makeFileWithPath(array $file, string $dir)
    {
        $this->makePath($dir);
        return $this->uploadFile($file, $dir);
    }

    /**
     * @throws FileException
     */
    private function makePath(string $dir)
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
     * @return mixed
     * @throws FileException
     */
    public function makeDir($name, string $dir)
    {
        $argc = [
            'action' => 'folder',
            'path' => "/domains/" . $this->params['domain'] . "/public_html/" . $dir,
            'name' => $name,
        ];
        try {
            $response = $this->url([
                $this->uri['CMD_API_FILE_MANAGER'] . "?" . http_build_query($argc),
            ], false, true)->request('POST', $argc);
        } catch (FileException $ex) {
            throw new FileException($ex->getMessage());
        }
        parse_str($response, $output);
        if (isset($output['error']) && $output['error'] == 1 && !str_contains($output['details'], "The path already exists")) {
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
        } catch (FileException $ex) {
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
            array_unshift(
                $this->options[CURLOPT_HTTPHEADER],
                "X-DirectAdmin-File-Upload: yes",
                "X-DirectAdmin-File-Name: " . $data['filename']
            );

            return $data['content'];
        } else {
            return $data;
        }
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
     * @param mixed $response
     * @return mixed
     */
    protected function parseResponse($response)
    {
        return $response;
    }
}
