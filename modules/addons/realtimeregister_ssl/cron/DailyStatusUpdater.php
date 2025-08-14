<?php

namespace AddonModule\RealtimeRegisterSsl\cron;

use AddonModule\RealtimeRegisterSsl\Addon;
use AddonModule\RealtimeRegisterSsl\eHelpers\Whmcs;
use AddonModule\RealtimeRegisterSsl\eModels\whmcs\service\SSL;
use AddonModule\RealtimeRegisterSsl\eRepository\whmcs\service\SSL as SSLRepo;
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
            Addon::I();

            $this->sslRepo = new SSLRepo();
            $sslOrders = $this->getSSLOrders([SSL::PENDING_INSTALLATION, SSL::ACTIVE, SSL::CONFIGURATION_SUBMITTED]);

            Whmcs::savelogActivityRealtimeRegisterSsl(
                "Realtime Register SSL WHMCS: Certificates (ssl status Completed) Data Updater started."
            );

            $this->checkOrdersStatus($sslOrders);

            logActivity('Realtime Register SSL: Certificates (ssl status Completed) Data Updater completed.');
            Whmcs::savelogActivityRealtimeRegisterSsl(
                "Realtime Register SSL WHMCS: Certificates (ssl status Completed) Data Updater completed."
            );
        }
    }
}
