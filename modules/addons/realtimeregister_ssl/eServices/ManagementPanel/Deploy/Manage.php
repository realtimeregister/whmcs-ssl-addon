<?php

declare(strict_types=1);

namespace AddonModule\RealtimeRegisterSsl\eServices\ManagementPanel\Deploy;

use Exception;
use AddonModule\RealtimeRegisterSsl\eServices\ManagementPanel\Deploy\Api\Platforms\PlatformInterface;
use AddonModule\RealtimeRegisterSsl\addonLibs\exceptions\DeployException;

class Manage
{
    protected array $panel;
    /**
     * @var PlatformInterface
     */
    protected $api;


    /**
     * @throws DeployException
     */
    public function __construct(array $panelData, $options = []) {
        $this->panel = $panelData;

        $API = sprintf("\AddonModule\RealtimeRegisterSsl\\eServices\ManagementPanel\Deploy\Api\Platforms\%s",
            ucfirst($panelData['platform']));

        if (!class_exists($API)) {
            throw new DeployException(
                sprintf("Platform `%s` not supported. Can not find class '%s'", $panelData['platform'], $API),
                12);
        }

        $this->api = new $API($panelData + $options);
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
        $this->api->uploadCertificate($domain, $crt);
        return $this->api->installCertificate($domain, $key, $crt, $csr, $ca);
    }

    /**
     * @throws Exception
     */
    public function prepareDeploy($domain, $crt = null, $csr = null, $key = null, $caBundle = null) : string
    {
        try {
            return $this->deployCertificate($domain, $key, $crt, $csr, $caBundle);
        } catch (Exception $e) {
            logActivity("realtimeregister_ssl. CronJob. Deploy Error: " . $e->getMessage());
            throw $e;
        }
    }
}
