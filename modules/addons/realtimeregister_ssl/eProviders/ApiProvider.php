<?php

declare(strict_types=1);

namespace AddonModule\RealtimeRegisterSsl\eProviders;

use Exception;
use Illuminate\Database\Capsule\Manager as Capsule;
use RealtimeRegister\Api\AbstractApi;
use RealtimeRegister\Support\AuthorizedClient;

class ApiProvider
{
    private static $instance;

    private const API_URL = 'https://api.yoursrs.com';
    private const API_TEST_URL = 'https://api.yoursrs-ote.com';

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

    public static function standalone(string $className, string $apiLogin, bool $isTest) {
        return new $className(new AuthorizedClient(self::getUrl($isTest), $apiLogin));
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
        $apiKeyRecord = Capsule::table('REALTIMEREGISTERSSL_api_configuration')->first();

        $apiUrl = self::getUrl($apiKeyRecord->api_test === 1);
        $this->api[$className] = new $className(
            new AuthorizedClient($apiUrl, decrypt($apiKeyRecord->api_login, $GLOBALS['cc_encryption_hash']))
        );
        self::$customer = self::parseCustomer(decrypt($apiKeyRecord->api_login, $GLOBALS['cc_encryption_hash']));
    }

    public static function parseCustomer(string $apiKey): string
    {
        $tmp = base64_decode($apiKey);
        return explode('/', $tmp)[0];
    }


    public static function getCustomer(): string
    {
        return self::$customer;
    }

    private static function getUrl(bool $isTest) {
        return $isTest ? self::API_TEST_URL : self::API_URL;
    }
}
