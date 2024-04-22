<?php

namespace MGModule\RealtimeRegisterSsl\eServices\Deploy;

use MGModule\RealtimeRegisterSsl\eServices\Deploy\API\Platforms\PlatformInterface;
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
     * @param $domain
     * @param array $csrData
     * @return array [csr, key, keyid]
     * @throws \Exception
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
     * @param $id
     * @return array
     * @throws \Exception
     */
    public static function getKey(string $domain, $id)
    {
        self::loadPanel($domain);

        return self::$instance->getKey($domain, $id);
    }

    /**
     * @param $domain
     * @param $crt
     * @return mixed
     * @throws \Exception
     */
    public static function uploadCertificate(string $domain, $crt)
    {
        self::loadPanel($domain);

        return self::$instance->uploadCertificate($domain, $crt);
    }

    /**
     * @param string $key
     * @param string $crt
     * @param string $csr
     * @param string $ca
     * @return string
     * @throws \Exception
     */
    public static function deployCertificate(string $domain, $key, $crt, $csr = null, $ca = null)
    {
        self::loadPanel($domain);

        self::$instance->uploadCertificate($domain, $crt);
        self::$instance->installCertificate($domain, $key, $crt, $csr, $ca);

        return "success";
    }


    /**
     * @param \HostcontrolPanel\Manage $panel
     * @param array $options
     * @throws \Exception
     */
    public static function loadPanel(string $domain, $options = [])
    {
        $panel = new \HostcontrolPanel\Manage($domain);

        if (!isset(self::$instance)) {
            self::$instance = self::makeInstance($panel, $options);
        }
    }

    public static function prepareDeply($sid, string $domain, $crt = null)
    {
        $manage = new \HostcontrolSSL\Manage(['serviceid' => $sid, 'domainname' => $domain]);
        /** @var \HostcontrolSSL\Service\Certificate $Certificate */
        $Certificate = $manage->service('certificate');

        $key = $Certificate->getRsa($sid);

        if (!$crt) {
            $crt = $Certificate->getCertificate(null, 'crt');
        }
        $csr = \MGModule\RealtimeRegisterSsl\eServices\Validation\Manage::getCsrByServiceId($sid);

        try {
            self::deployCertificate($domain, $key, $crt, $csr);
        } catch (\Exception $e) {
            logActivity("Realtime Register Ssl. CronJob. Deploy Error: " . $e->getMessage());
        }
    }

    /**
     * @param \HostcontrolPanel\Manage $panel
     * @param array $options
     * @return \HostcontrolSSL\Services\Deploy\API\Client
     * @throws \Exception
     */
    private static function makeInstance($panel, $options)
    {
        $panelData = $panel->getPanelData();
        self::$panel = $panelData['platform'];
        $api = sprintf(
            '\MGModule\RealtimeRegisterSsl\eServices\Deploy\API\Platforms\%s',
            ucfirst($panelData['platform'])
        );

        if (!class_exists($api)) {
            throw new DeployException(sprintf("Platform `%s` not supported.", $panelData['platform']), 12);
        }

        return new $api($panelData + $options);
    }
}
