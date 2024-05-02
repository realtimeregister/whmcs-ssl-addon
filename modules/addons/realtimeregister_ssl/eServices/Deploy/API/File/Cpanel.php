<?php

declare(strict_types=1);

namespace MGModule\RealtimeRegisterSsl\eServices\Deploy\API\File;

use MGModule\RealtimeRegisterSsl\eServices\Deploy\API\Client;
use MGModule\RealtimeRegisterSsl\mgLibs\exceptions\FileException;

class Cpanel extends Client implements PlatformInterface
{
    private int $port = 2083;

    private array $uri = [
        'save_file_content' => 'json-api/cpanel?cpanel_jsonapi_apiversion=2' .
            '&cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=savefile',
        'make_dir'          => 'json-api/cpanel?cpanel_jsonapi_apiversion=2' .
            '&cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=mkdir',
        'search_list'       => 'json-api/cpanel?cpanel_jsonapi_apiversion=2' .
            '&cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=listfiles',
        'get_file_content'  => 'json-api/cpanel?cpanel_jsonapi_apiversion=3&' .
            'cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=get_file_content',
    ];

    private string $contentType = "Content-Type: application/x-www-form-urlencoded";

    public function __construct(array $params)
    {
        $params['contentType'] = $this->contentType;
        $params['API_PORT'] = $params['API_PORT'] ? $params['API_PORT'] : $this->port;
        $params['API_URL'] = sprintf("%s/", $params['API_URL']);
        $params['debug'] = 'on';
        parent::__construct($params);
    }

    /**
     * @param array $file [name,content]
     * @param string $dir
     * @return mixed
     */
    public function uploadFile($file, $dir)
    {
        $args = [
            'dir' => $this->getMainDir() . $dir,
            'filename' => $file['name'],
            'content' => $file['content']
        ];

        try {
            $response = $this->url([$this->uri['save_file_content']], false, true)->request('POST', $args);
        } catch(\Exception $ex) {
            if(strpos($ex->getMessage(), "The file “” does not exist") !== FALSE) {
                return $this->makeFileWithPath($file, $dir);
            } else {
                throw new FileException($ex->getMessage());
            }
        }

        if(isset($response->cpanelresult->error)) {
            throw new FileException($response->cpanelresult->error);
        }

        return "success";
    }

    /**
     * @throws FileException
     */
    private function makeFileWithPath($file, $dir) {
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
    private function makePath($dir) {
        $mainDir = "";
        $dirSplit = explode('/', $dir);
        foreach($dirSplit as $subDir) {
            if($subDir == "") {
                continue;
            }
            try {
                $this->makeDir($subDir, $mainDir);
            } catch(FileException $e) {}
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
        $args = [
            'path' => $this->getMainDir() . $dir,
            'name' => $name
        ];
        try {
            $response = $this->url([$this->uri['make_dir']], false, true)->request('POST', $args);
        } catch(\Exception $ex) {
            throw new FileException($ex->getMessage());
        }

        if(isset($response->cpanelresult->error)) {
            throw new FileException($response->cpanelresult->error);
        }

        return "success";
    }

    /**
     * @param string $file
     * @param string $dir
     * @return mixed
     * @throws FileException
     */
    public function getFile($file, $dir)
    {
        $args = [
            'dir' => $this->getMainDir() . $dir,
            'file' => $file['name'],
        ];

        try {
            $response = $this->url([$this->uri['get_file_content']], false, true)->request('POST', $args);
        } catch(\Exception $ex) {
            throw new FileException($ex->getMessage());
        }
        if(!empty($response->result->errors)) {
            $error = $response->result->errors[0];
            if(strpos($error, "does not exist for the account") !== false) {
                return false;
            }
            throw $error;
        }

        return [ 'content' => $response->result->data->content];
    }

    protected function setAuth(): void
    {
        array_unshift($this->options[CURLOPT_HTTPHEADER], sprintf("Authorization: Basic %s",
            base64_encode($this->params['API_USER'] . ":" . $this->params['API_PASSWORD'])));
    }

    /**
     * @param mixed $response
     * @return mixed
     * @throws FileException
     */
    protected function parseResponse($response)
    {
        $result = json_decode($response);
        if(isset($result->cpanelresult) && isset($result->cpanelresult->error)) {
            if(isset($result->cpanelresult->data->reason)) {
                throw new FileException($result->cpanelresult->data->reason);
            } else {
                throw new FileException($result->cpanelresult->error);
            }
        }

        return $result;
    }

    private function getMainDir(): string
    {
        return "/home/" . $this->params['API_USER'] . "/public_html/";
    }
}