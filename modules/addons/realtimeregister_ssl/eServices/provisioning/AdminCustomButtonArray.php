<?php

namespace AddonModule\RealtimeRegisterSsl\eServices\provisioning;

use AddonModule\RealtimeRegisterSsl\eModels\whmcs\service\SSL;
use AddonModule\RealtimeRegisterSsl\eRepository\whmcs\service\SSL as SSLRepo;

class AdminCustomButtonArray
{
    private array $p;

    public function __construct(array $params)
    {
        $this->p = &$params;
    }

    public function run(): array
    {
        $sslOrder = (new SSLRepo())->getByServiceId($this->p['serviceid']);
        $buttons = [
            'Manage SSL' => 'SSLAdminManageSSL',
        ];

        if ($sslOrder->status === SSL::CONFIGURATION_SUBMITTED) {
            $buttons['Refresh'] = '';
        }

        return $buttons;
    }
}
