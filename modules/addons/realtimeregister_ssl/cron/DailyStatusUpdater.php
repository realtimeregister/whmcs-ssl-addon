<?php

namespace AddonModule\RealtimeRegisterSsl\cron;

use AddonModule\RealtimeRegisterSsl\eHelpers\Whmcs;
use AddonModule\RealtimeRegisterSsl\eModels\whmcs\service\SSL;
use Illuminate\Database\Capsule\Manager as Capsule;

class DailyStatusUpdater extends BaseTask
{
    protected $skipDailyCron = false;
    protected $defaultPriority = 4200;
    protected $defaultDescription = 'automatic daily synchronization';
    protected $defaultName = "Daily status updater";

    public function __invoke()
    {
        if ($this->enabledTask('cron_certificate_details_updater')) {
            logActivity('Realtime Register SSL: Certificates (ssl status Completed) Data Updater started');

            $sslorders = Capsule::table('tblhosting')
                ->join('tblproducts', 'tblhosting.packageid', '=', 'tblproducts.id')
                ->join('tblsslorders', 'tblsslorders.serviceid', '=', 'tblhosting.id')
                ->where('tblhosting.domainstatus', 'Active')
                ->whereIn('tblsslorders.status', [SSL::PENDING_INSTALLATION, SSL::ACTIVE, SSL::CONFIGURATION_SUBMITTED])
                ->get(['tblsslorders.*']);

            Whmcs::savelogActivityRealtimeRegisterSsl(
                "Realtime Register SSL WHMCS: Certificates (ssl status Completed) Data Updater started."
            );

            $this->checkOrdersStatus($sslorders);

            logActivity('Realtime Register SSL: Certificates (ssl status Completed) Data Updater completed.');
            Whmcs::savelogActivityRealtimeRegisterSsl(
                "Realtime Register SSL WHMCS: Certificates (ssl status Completed) Data Updater completed."
            );
        }
    }
}
