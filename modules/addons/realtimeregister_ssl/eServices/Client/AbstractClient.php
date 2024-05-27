<?php

declare(strict_types=1);

namespace MGModule\RealtimeRegisterSsl\eServices\Client;

use GuzzleHttp\Utils;
use MGModule\RealtimeRegisterSsl\eServices\Config;

/**
 * Class AbstractClient
 */
abstract class AbstractClient
{
    /**
     * @var array
     */
    protected $args;

    /**
     * @param $url
     * @param string $type
     * @param array $options
     * @return mixed
     */
    abstract public function request($url, $type = 'GET', $options = []);

    /**
     * @return mixed
     */
    protected function getBaseUrl()
    {
        return $this->args['ote'] == 'on' ? Config::get('api.ote') : Config::get('api.production');
    }

    /**
     * Check if ignore SSL verify is true of false
     *
     * @return bool
     */
    protected function ignoreSslVerify()
    {
        return $this->args['ignore_ssl'] == 'on';
    }

    /**
     * @return array
     */
    protected function getAuth()
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
     *
     * @return string
     */
    protected function getUserAgent()
    {
        return Utils::getDefaultUserAgent() . ' WHMCS/' . $GLOBALS['CONFIG']['Version'] . ' Version/' . Config::get('addon.version');
    }
}
