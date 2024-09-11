<?php

namespace AddonModule\RealtimeRegisterSsl\eServices\ManagementPanel\Api\Panel;

use AddonModule\RealtimeRegisterSsl\eProviders\ApiProvider;
use AddonModule\RealtimeRegisterSsl\eServices\ManagementPanel\Api\Panel\Exceptions\PanelException;
use SandwaveIo\RealtimeRegister\Api\CertificatesApi;
use SandwaveIo\RealtimeRegister\Domain\Certificate;

class Manage
{
    private $panelData;
    private bool $debug;

    public function __construct($domain, $sid = null, $debug = false)
    {
        $this->panelData = Panel::getPanelData($domain, $sid);
        $this->debug = $debug;
    }

    public function getPanelData()
    {
        if (!$this->panelData) {
            throw new PanelException("Panel Data not found");
        }
        return $this->panelData;
    }

    public function isPanelActive()
    {
        return $this->panelData['status'] == "Active";
    }
}
