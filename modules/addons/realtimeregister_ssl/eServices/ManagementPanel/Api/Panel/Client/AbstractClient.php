<?php

namespace AddonModule\RealtimeRegisterSsl\eServices\ManagementPanel\Api\Panel\Client;

use GuzzleHttp\Utils;

/**
 * Class AbstractClient
 */
abstract class AbstractClient
{
    protected array $args;

    /**
     * @return mixed
     */
    abstract public function request(string $url, string $type = 'GET', array $options = []);

    protected function getBaseUrl(): string
    {
        return $this->args['API_URL'] . ":" . $this->args['API_PORT'];
    }

    protected function getAuth(): array
    {
        return [
            $this->args['API_USER'],
            $this->args['API_PASSWORD'],
        ];
    }

    /**
     * Get user agent string
     */
    protected function getUserAgent(): string
    {
        return Utils::getDefaultUserAgent() . ' WHMCS/' . $GLOBALS['CONFIG']['Version'] . ' Version/' . $this->args['version'];
    }
}
