<?php

declare(strict_types=1);

namespace MGModule\RealtimeRegisterSsl\eServices\Deploy;

use Exception;
use HostcontrolSSL\Service\Certificate;
use MGModule\RealtimeRegisterSsl\eServices\Deploy\API\Client;
use MGModule\RealtimeRegisterSsl\eServices\Deploy\API\Ssl\PlatformInterface;
use MGModule\RealtimeRegisterSsl\mgLibs\exceptions\DeployException;

class Manage
{
    protected static Panel $panel;
    private static PlatformInterface $instance;

    /**
     * @param $domain
     * @param array $csrData
     * @return array [csr, key, keyid]
     * @throws Exception
     */
    public static function genKeyCsr($domain, $csrData)
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
     * @param string $domain
     * @param $id
     * @return array
     * @throws Exception
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
    public static function uploadCertificate($domain, $crt)
    {
        self::loadPanel($domain);

        return self::$instance->uploadCertificate($domain, $crt);
    }

    /**
     * @throws \Exception
     */
    public static function deployCertificate(
        string $domain, string $key, string $crt, string $csr = null, string $ca = null
    ): string {
        self::loadPanel($domain);

        self::$instance->uploadCertificate($domain, $crt);
        self::$instance->installCertificate($domain, $key, $crt, $csr, $ca);

        return "success";
    }

    /**
     * @param array $options
     * @throws \Exception
     */
    public static function loadPanel(string $domain, $options = [])
    {
        $panel = new Panel\Manage($domain);

        if (!isset(self::$instance)) {
            self::$instance = self::makeInstance($panel, $options);
        }
    }

    public static function prepareDeply($sid, $domain, $crt = null)
    {
        $manage = new Panel\Manage(['serviceid' => $sid, 'domainname' => $domain]);
        /** @var Certificate $certificate */
        $certificate = $manage->service('certificate');

        $key = $certificate->getRsa($sid);

        if (!$crt) {
            $crt = $certificate->getCertificate(null, 'crt');
        }
        $csr = \HostcontrolSSL\Services\Validation\Manage::getCsrByServiceId($sid);

        try {
            self::deployCertificate($domain, $key, $crt, $csr);
        } catch (\Exception $e) {
            logActivity("Realtime Register Ssl. CronJob. Deploy Error: " . $e->getMessage());
        }
    }

    /**
     * @param \MGModule\RealtimeRegisterSsl\eServices\Deploy\Panel\Manage $panel
     * @param array $options
     * @return Client
     * @throws Exception
     */
    private static function makeInstance($panel, $options)
    {
        $panelData = $panel->getPanelData();
        self::$panel = $panelData['platform'];
        $api = sprintf('\MGModule\RealtimeRegisterSsl\eServices\Deploy\API\Ssl\%', ucfirst($panelData['platform']));

        if (!class_exists($api)) {
            throw new DeployException(sprintf('Platform `%s` not supported.', $panelData['platform']), 12);
        }

        return new $api($panelData + $options);
    }
}
