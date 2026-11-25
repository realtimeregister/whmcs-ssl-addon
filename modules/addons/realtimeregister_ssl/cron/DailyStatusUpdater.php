<?php

namespace AddonModule\RealtimeRegisterSsl\cron;

use AddonModule\RealtimeRegisterSsl\Addon;
use AddonModule\RealtimeRegisterSsl\eHelpers\Whmcs;
use AddonModule\RealtimeRegisterSsl\eRepository\whmcs\service\SSL as SSLRepo;

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
            $sslOrderIds = $this->getSSLOrders();

            Whmcs::savelogActivityRealtimeRegisterSsl(
                "Realtime Register SSL WHMCS: Certificates (ssl status Completed) Data Updater started."
            );

            $this->checkOrdersStatus($sslOrderIds, self::DEFAULT_STATUSES, self::DAILY_LOCK_WAIT);

            logActivity('Realtime Register SSL: Certificates (ssl status Completed) Data Updater completed.');
            Whmcs::savelogActivityRealtimeRegisterSsl(
                "Realtime Register SSL WHMCS: Certificates (ssl status Completed) Data Updater completed."
            );
        }
    }
}
