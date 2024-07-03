<?php

declare(strict_types=1);

namespace MGModule\RealtimeRegisterSsl\eServices\ManagementPanel\Client;

use GuzzleHttp\Utils;

abstract class AbstractClient
{
    protected array $args;

    /**
     * @return mixed
     */
    abstract public function request(string $url, string $type = 'GET', array $options = []);

    /**
     * @return mixed
     */
    protected function getBaseUrl()
    {
        return $this->args['ote'] == 'on' ? Config::get('api.ote') : Config::get('api.production');
    }

    protected function url(string $endpoint)
    {
        return $this->getBaseUrl() . $endpoint;
    }

    protected function getVersion() {
        return "1.0";
    }

    /**
     * Check if ignore SSL verify is true of false
     *
     */
    protected function ignoreSslVerify(): bool
    {
        return $this->args['ignore_ssl'] == 'on';
    }

    protected function getAuth(): array
    {
        return [
            $this->args['apiUser'],
            decrypt(
                $this->args['apiPassword'],
                $GLOBALS['cc_encryption_hash']
            )
        ];
    }

    /**
     * Get user agent string
     */
    protected function getUserAgent()
    {
        return Utils::defaultUserAgent() . ' WHMCS/' . $GLOBALS['CONFIG']['Version'] . ' Version/' . $this->getVersion();
    }
}
