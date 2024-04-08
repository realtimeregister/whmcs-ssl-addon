<?php

namespace MGModule\RealtimeRegisterSsl\eProviders;

use Exception;

class ApiProvider {

    /**
     *
     * @var type 
     */
    private static $instance;
    
    /**
     *
     * @var \MGModule\RealtimeRegisterSsl\mgLibs\RealtimeRegisterSsl
     */
    private $api;

    /**
     * @return ApiProvider
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new ApiProvider();
        }
        return self::$instance;
    }

    /**
     * @return \MGModule\RealtimeRegisterSsl\mgLibs\RealtimeRegisterSsl
     */
    public function getApi($exception = true) {
        if ($this->api === null) {
            $this->initApi();
        }
        
        if($exception) {
            $this->api->setRealtimeRegisterSslApiException();
        } else {
            $this->api->setNoneException();
        }
        
        return $this->api;
    }

    /**
     * @throws Exception
     */
    private function initApi() {
        new \MGModule\RealtimeRegisterSsl\mgLibs\RealtimeRegisterSsl(); // need fix and remove that line xD
        $apiData = $this->getCredencials();
        $this->api = new \MGModule\RealtimeRegisterSsl\mgLibs\RealtimeRegisterSsl();
        $this->api->auth($apiData->api_login, $apiData->api_password);
    }
    
    private function getCredencials() {
        $apiConfigRepo = new \MGModule\RealtimeRegisterSsl\models\apiConfiguration\Repository();
        $apiData       = $apiConfigRepo->get();
        if (empty($apiData->api_login) || empty($apiData->api_password)) {
            throw new \MGModule\RealtimeRegisterSsl\mgLibs\RealtimeRegisterException('api_configuration_empty');
        }
        return $apiData;
    }
}
