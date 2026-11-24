<?php

namespace AddonModule\RealtimeRegisterSsl\cron;

use AddonModule\RealtimeRegisterSsl\Addon;
use AddonModule\RealtimeRegisterSsl\eHelpers\Whmcs;
use AddonModule\RealtimeRegisterSsl\eModels\whmcs\service\SSL;
use AddonModule\RealtimeRegisterSsl\eRepository\whmcs\service\SSL as SSLRepo;

class ProcessingOrders extends BaseTask
{
    protected $defaultFrequency = 5;
    protected $skipDailyCron = true;
    protected $defaultPriority = 4200;
    protected $defaultDescription = 'automatic synchronization of processing orders';
    protected $defaultName = "Processing orders";

    public function __invoke()
    {
        if ($this->enabledTask('cron_processing')) {
            logActivity("Realtime Register SSL: Certificates (ssl status Processing) Data Updater started");
            Addon::I();

            $this->sslRepo = new SSLRepo();
            $status = [SSL::CONFIGURATION_SUBMITTED];
            $sslOrderIds = $this->getSSLOrders($status);

            Whmcs::savelogActivityRealtimeRegisterSsl(
                "Realtime Register SSL WHMCS: Certificates (ssl status Processing) Data Updater started."
            );

            $this->checkOrdersStatus($sslOrderIds, $status);

            Whmcs::savelogActivityRealtimeRegisterSsl(
                "Realtime Register SSL WHMCS: Certificates (ssl status Processing) Data Updater completed."
            );
        }
    }
}
