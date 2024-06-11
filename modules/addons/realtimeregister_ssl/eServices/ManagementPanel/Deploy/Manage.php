<?php

declare(strict_types=1);

namespace MGModule\RealtimeRegisterSsl\eServices\ManagementPanel\Deploy;

use Exception;
use MGModule\RealtimeRegisterSsl\eServices\ManagementPanel\Deploy\Api\Platforms\PlatformInterface;
use MGModule\RealtimeRegisterSsl\mgLibs\exceptions\DeployException;

class Manage
{
    /**
     * @var Panel
     */
    protected static $panel;
    /**
     * @var PlatformInterface
     */
    private static $instance;

    /**
     * @return array [csr, key, keyid]
     * @throws Exception
     */
    public static function genKeyCsr(string $domain, array $csrData)
    {
        self::loadPanel($domain);

        $result = self::$instance->genKeyCsr($domain, $csrData);

        if (isset($result['csr']) && isset($result['key'])) {
            return [
                $result['csr'],
                $result['key'],
                $result['keyid']
            ];
        }

        throw new DeployException("Unknown Error");
    }

    /**
     * @param $domain
     * @param $id
     * @return array
     * @throws Exception
     */
    public static function getKey($domain, $id)
    {
        self::loadPanel($domain);

        return self::$instance->getKey($domain, $id);
    }

    /**
     * @param $domain
     * @param $crt
     * @return mixed
     * @throws Exception
     */
    public static function uploadCertificate($domain, $crt)
    {
        self::loadPanel($domain);

        return self::$instance->uploadCertificate($domain, $crt);
    }

    /**
     * @param string $domain
     * @param string $key
     * @param string $crt
     * @param string $csr
     * @param string $ca
     * @return string
     * @throws Exception
     */
    public static function deployCertificate($domain, $key, $crt, $csr = null, $ca = null)
    {
        self::loadPanel($domain);

        self::$instance->uploadCertificate($domain, $crt);
        self::$instance->installCertificate($domain, $key, $crt, $csr, $ca);

        return "success";
    }

    /**
     * @param array $options
     * @throws Exception
     */
    public static function loadPanel($domain, $options = [])
    {
        $panel = new \HostcontrolPanel\Manage($domain);

        if (!isset(self::$instance)) {
            self::$instance = self::makeInstance($panel, $options);
        }
    }

    public static function prepareDeply($sid, $domain, $crt = null)
    {
        $manage = new \HostcontrolSSL\Manage(['serviceid' => $sid, 'domainname' => $domain]);
        /** @var \HostcontrolSSL\Service\Certificate $Certificate */
        $Certificate = $manage->service('certificate');

        $key = $Certificate->getRsa($sid);

        if (!$crt) {
            $crt = $Certificate->getCertificate(null, 'crt');
        }
        $csr = \HostcontrolSSL\Services\Validation\Manage::getCsrByServiceId($sid);

        try {
            self::deployCertificate($domain, $key, $crt, $csr);
        } catch (Exception $e) {
            logActivity("hostcontro_ssl. CronJob. Deploy Error: " . $e->getMessage());
        }
    }

    /**
     * @param \HostcontrolPanel\Manage $panel
     * @param array $options
     * @return \HostcontrolSSL\Services\Deploy\API\Client
     * @throws Exception
     */
    private static function makeInstance($panel, $options)
    {
        $panelData = $panel->getPanelData();
        self::$panel = $panelData['platform'];
        $API = sprintf("\HostcontrolSSL\Services\Deploy\API\Platforms\%s", ucfirst($panelData['platform']));

        if (!class_exists($API)) {
            throw new DeployException(sprintf("Platform `%s` not supported.", $panelData['platform']), 12);
        }

        return new $API($panelData + $options);
    }
}
