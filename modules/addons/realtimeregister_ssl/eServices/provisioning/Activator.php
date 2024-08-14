<?php

namespace MGModule\RealtimeRegisterSsl\eServices\provisioning;

use Exception;
use MGModule\RealtimeRegisterSsl\eServices\FlashService;
use WHMCS\Service\Service;

class Activator
{
    public function run()
    {
        try {
            $this->activator();
        } catch (Exception $ex) {
        }
    }

    private function activator()
    {
        $serviceId = FlashService::getAndUnset('realtimeregister_ssl_WHMCS_SERVICE_TO_ACTIVE');
        if (is_null($serviceId)) {
            return;
        }
        $service               = Service::findOrFail($serviceId);
        $service->domainstatus = 'Active';
        $service->save();
    }
}
