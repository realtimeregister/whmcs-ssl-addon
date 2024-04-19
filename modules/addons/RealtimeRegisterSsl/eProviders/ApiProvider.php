<?php

declare(strict_types=1);

namespace MGModule\RealtimeRegisterSsl\eProviders;

use Exception;
use Illuminate\Database\Capsule\Manager as Capsule;
use SandwaveIo\RealtimeRegister\Api\AbstractApi;
use SandwaveIo\RealtimeRegister\Support\AuthorizedClient;

class ApiProvider
{
    private static $instance;

    private string $apiUrl = 'https://api.yoursrs-ote.com';

    /**
     * @var AbstractApi[]
     */
    private array $api = [];
    private static string $customer;

    public static function getInstance(): ApiProvider
    {
        if (self::$instance === null) {
            self::$instance = new ApiProvider();
        }
        return self::$instance;
    }

    /**
     * @throws Exception
     */
    public function getApi(string $className): AbstractApi
    {
        if ($this->api[$className] === null) {
            $this->initApi($className);
        }
        
        return $this->api[$className];
    }

    /**
     * @throws Exception
     */
    private function initApi(string $className): void
    {
        $apiKeyRecord = Capsule::table('mgfw_REALTIMEREGISTERSSL_api_configuration')->first();

        $this->api[$className] = new $className(new AuthorizedClient($this->apiUrl, $apiKeyRecord->api_login));
        self::$customer = $this->setCustomer($apiKeyRecord->api_login);
    }

    private function setCustomer(string $apiKey): string
    {
        $tmp = base64_decode($apiKey);
        return explode('/', $tmp)[0];
    }

    public static function getCustomer(): string
    {
        return self::$customer;
    }
}
