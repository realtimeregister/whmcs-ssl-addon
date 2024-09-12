<?php

declare(strict_types=1);

namespace AddonModule\RealtimeRegisterSsl\eServices\ManagementPanel\Deploy;

use Exception;
use AddonModule\RealtimeRegisterSsl\eServices\ManagementPanel\Deploy\Api\Platforms\PlatformInterface;
use AddonModule\RealtimeRegisterSsl\addonLibs\exceptions\DeployException;

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
        return self::$instance->installCertificate($domain, $key, $crt, $csr, $ca);
    }

    /**
     * @param array $options
     * @throws Exception
     */
    public static function loadPanel($domain, $options = [])
    {
        $panel = new \AddonModule\RealtimeRegisterSsl\eServices\ManagementPanel\Api\Panel\Manage($domain);

        if (!isset(self::$instance)) {
            self::$instance = self::makeInstance($panel, $options);
        }
    }

    /**
     * @throws Exception
     */
    public static function prepareDeploy($sid, $domain, $crt = null, $csr = null, $key = null, $caBundle = null) : string
    {
        try {
            return self::deployCertificate($domain, $key, $crt, $csr, $caBundle);
        } catch (Exception $e) {
            logActivity("realtimeregister_ssl. CronJob. Deploy Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * @param \AddonModule\RealtimeRegisterSsl\eServices\ManagementPanel\Api\Panel\Manage $panel
     * @param array $options
     * @return \AddonModule\RealtimeRegisterSsl\eServices\ManagementPanel\Deploy\Api\Platforms\PlatformInterface
     * @throws Exception
     */
    private static function makeInstance($panel, $options)
    {
        $panelData = $panel->getPanelData();
        self::$panel = $panelData['platform'];
        $API = sprintf("\AddonModule\RealtimeRegisterSsl\\eServices\ManagementPanel\Deploy\Api\Platforms\%s",
            ucfirst($panelData['platform']));

        if (!class_exists($API)) {
            throw new DeployException(sprintf("Platform `%s` not supported.", $panelData['platform']), 12);
        }

        return new $API($panelData + $options);
    }
}
