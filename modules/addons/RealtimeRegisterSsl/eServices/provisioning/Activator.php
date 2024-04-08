<?php

namespace MGModule\RealtimeRegisterSsl\eServices\provisioning;

use Exception;

class Activator {

    public function run() {
        try {
            $this->activator();
        } catch (Exception $ex) {
            
        }
    }

    private function activator() {
        $serviceId = \MGModule\RealtimeRegisterSsl\eServices\FlashService::getAndUnset('REALTIMEREGISTERSSL_WHMCS_SERVICE_TO_ACTIVE');
        if (is_null($serviceId)) {
            return;
        }
        $service               = \WHMCS\Service\Service::findOrFail($serviceId);
        $service->domainstatus = 'Active';
        $service->save();
    }

}
