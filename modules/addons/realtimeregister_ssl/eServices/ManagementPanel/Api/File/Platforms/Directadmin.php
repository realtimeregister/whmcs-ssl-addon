<?php

namespace AddonModule\RealtimeRegisterSsl\eServices\ManagementPanel\Api\File\Platforms;

use AddonModule\RealtimeRegisterSsl\eServices\ManagementPanel\Api\File\Client;
use AddonModule\RealtimeRegisterSsl\eServices\ManagementPanel\Api\File\Exceptions\FileException;
use CURLFile;

class Directadmin extends Client implements PlatformInterface
{
    private int $port = 2222;

    private array $uri = [
        'upload' => "/api/filemanager-actions/upload",
        'mkdir' => "/api/filemanager-actions/mkdir",
    ];

    private string $contentType = "Content-Type: application/json";

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
        $path = substr($dir, 0, (strlen($this->params['domain']) + 1));
        $argc = [
            'path' => "/domains/" . $this->params['domain'] . "/public_html" . $path,
            'filename' => $file['name'],
            'content' => $file['content']
        ];


        try {
            $response = $this->url([$this->uri['upload']], false, true)
                ->requestMultiPart($argc);
        } catch (FileException $ex) {
            throw new FileException($ex->getMessage());
        }

        $output = json_decode($response, true);

        if (isset($output['reason'])) {
            if ($this->i == 0) {
                $this->i++;

                return $this->makeFileWithPath($file, $argc['path']);
            } else {
                throw new FileException($output['type'] . '|' . $output['reason']);
            }
        }

        return "success";
    }


    private function makeFileWithPath(array $file, string $dir)
    {
        $this->makeDir($dir);
        return $this->uploadFile($file, $dir);
    }

    /**
     * @param array $name
     * @return mixed
     * @throws FileException
     */
    public function makeDir(string $dir)
    {
        $body = [
            'path' => $dir
        ];

        try {
            $response = $this->url([
                $this->uri['mkdir']
            ], false, true)->request('POST', $body);
        } catch (FileException $ex) {
            throw new FileException($ex->getMessage());
        }

        $output = json_decode($response, true);
        if (isset($output['reason']) && !str_contains($output['reason'], "already exists")) {
            throw new FileException($output['type'] . $output['reason']);
        }

        return "success";
    }

    /**
     * @throws FileException
     */
    protected function requestMultiPart($post = [], $content = null)
    {
        $curl = curl_init();

        $this->method = 'POST';
        $this->request = [];
        $this->response = [];
        $this->postData = (object)$post;

        // Create temp file
        $tmp = tmpfile();
        fwrite($tmp, $post['content']);
        rewind($tmp);

        $meta = stream_get_meta_data($tmp);
        $tmpPath = $meta['uri'];

        $post = [
            'dir' => $post['path'],
            'overwrite' => true,
            'file' => new CURLFile($tmpPath, 'text/plain', $post['filename'])
        ];

        $this->setOptions('POST', $post, "Content-Type: multipart/form-data");
        curl_setopt_array($curl, $this->options);

        $cResponse = curl_exec($curl);
        // Split header and body.
        $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $header = substr($cResponse, 0, $header_size);
        $body = substr($cResponse, $header_size);

        // Store all for further use.
        $this->response = [
            'header' => $header,
            'body' => $body,
        ];

        $info = curl_getinfo($curl);

        $this->http_code = $info['http_code'];
        $this->request['header'] = $info['request_header'];
        $this->request = array_reverse($this->request);
        $error = curl_error($curl);
        curl_close($curl);

        if ($this->params['debug'] == 'on') {
            $this->debug();
        }

        fclose($tmp);

        if ($cResponse) {
            return $this->parseResponse($body);
        }

        if ($error) {
            throw new FileException($error);
        }

        throw new FileException("Something went wrong");
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
        $this->request['data'] = $data;
        if ($data['file']) {
            return $data;
        }
        return json_encode((object)$data, JSON_UNESCAPED_SLASHES);
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
