<?php

namespace MGModule\RealtimeRegisterSsl\eProviders;

use Exception;
use Illuminate\Database\Capsule\Manager as Capsule;

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
        $apiKeyRecord = Capsule::table('mgfw_REALTIMEREGISTERSSL_api_configuration')->first();
        $this->api = new \MGModule\RealtimeRegisterSsl\mgLibs\RealtimeRegisterSsl($apiKeyRecord->api_login);
    }
}
