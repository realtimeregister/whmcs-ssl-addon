<?php

namespace AddonModule\RealtimeRegisterSsl\cron;

use AddonModule\RealtimeRegisterSsl\eHelpers\Whmcs;
use AddonModule\RealtimeRegisterSsl\eModels\whmcs\service\SSL;
use Illuminate\Database\Capsule\Manager as Capsule;

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

            $sslorders = Capsule::table('tblhosting')
                ->join('tblproducts', 'tblhosting.packageid', '=', 'tblproducts.id')
                ->join('tblsslorders', 'tblsslorders.serviceid', '=', 'tblhosting.id')
                ->where('tblhosting.domainstatus', 'Active')
                ->where('tblsslorders.configdata', 'like', '%"ssl_status":"COMPLETED"%')
                ->orWhere('tblsslorders.status', '=', SSL::CONFIGURATION_SUBMITTED)
                ->get(['tblsslorders.*']);

            Whmcs::savelogActivityRealtimeRegisterSsl(
                "Realtime Register SSL WHMCS: Certificates (ssl status Processing) Data Updater started."
            );

            $this->checkOrdersStatus($sslorders, true);

            Whmcs::savelogActivityRealtimeRegisterSsl(
                "Realtime Register SSL WHMCS: Certificates (ssl status Processing) Data Updater completed."
            );
        }
    }
}
