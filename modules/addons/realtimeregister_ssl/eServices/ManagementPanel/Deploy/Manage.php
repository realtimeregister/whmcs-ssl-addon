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
    protected $panel;
    /**
     * @var PlatformInterface
     */
    protected $api;


    /**
     * @param $domain
     * @param array $options
     * @throws Exception
     */
    public function __construct($domain, $options = [])
    {
        $this->panel = new \AddonModule\RealtimeRegisterSsl\eServices\ManagementPanel\Api\Panel\Manage($domain);
        $panelData = $this->panel->getPanelData();
        $API = sprintf("\AddonModule\RealtimeRegisterSsl\\eServices\ManagementPanel\Deploy\Api\Platforms\%s",
            ucfirst($panelData['platform']));

        if (!class_exists($API)) {
            throw new DeployException(sprintf("Platform `%s` not supported.", $panelData['platform']), 12);
        }

        $this->api =  new $API($panelData + $options);
    }

    /**
     * @param $domain
     * @param $id
     * @return array
     * @throws Exception
     */
    public function getKey($domain, $id)
    {
        $this->loadPanel($domain);

        return $this->getKey($domain, $id);
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
    public function deployCertificate($domain, $key, $crt, $csr = null, $ca = null)
    {
        $this->loadPanel($domain);

        $this->api->uploadCertificate($domain, $crt);
        return $this->api->installCertificate($domain, $key, $crt, $csr, $ca);
    }

    /**
     * @param array $options
     * @throws Exception
     */
    public function loadPanel($domain, $options = [])
    {
        $this->panel = new \AddonModule\RealtimeRegisterSsl\eServices\ManagementPanel\Api\Panel\Manage($domain);
    }

    /**
     * @throws Exception
     */
    public function prepareDeploy($sid, $domain, $crt = null, $csr = null, $key = null, $caBundle = null) : string
    {
        try {
            return $this->deployCertificate($domain, $key, $crt, $csr, $caBundle);
        } catch (Exception $e) {
            logActivity("realtimeregister_ssl. CronJob. Deploy Error: " . $e->getMessage());
            throw $e;
        }
    }
}
