<?php

namespace AddonModule\RealtimeRegisterSsl\cron;

use AddonModule\RealtimeRegisterSsl\eHelpers\Whmcs;
use AddonModule\RealtimeRegisterSsl\eModels\whmcs\service\SSL;
use AddonModule\RealtimeRegisterSsl\eRepository\whmcs\service\SSL as SSLRepo;
use AddonModule\RealtimeRegisterSsl\eServices\provisioning\UpdateConfigs;
use AddonModule\RealtimeRegisterSsl\models\apiConfiguration\Repository;

class BaseTask extends \WHMCS\Scheduling\Task\AbstractTask
{
    /**
     * @var SSLRepo
     */
    protected $sslRepo = null;

    protected function enabledTask(string $taskName): bool
    {
        $apiConfigRepo = new Repository();
        $input = (array)$apiConfigRepo->get();

        if (array_key_exists($taskName, $input)) {
            return $input[$taskName];
        }

        return false;
    }

    protected function checkOrdersStatus($sslorders, $processingOnly = false)
    {
        $cids = [];
        foreach ($sslorders as $sslorder) {
            $cids[] = $sslorder->remoteid;
        }

        try {
            $configDataUpdate = new UpdateConfigs($cids, $processingOnly);
            $configDataUpdate->run();
        } catch (\Exception $e) {
            Whmcs::savelogActivityRealtimeRegisterSsl(
                "Realtime Register SSL WHMCS Products Price Updater Error: " . $e->getMessage()
            );
        }
    }

    protected function getSSLOrders($status = [SSL::CONFIGURATION_SUBMITTED, SSL::PENDING_INSTALLATION, SSL::ACTIVE])
    {
        return $this->sslRepo->getOrdersWithStatus($status);
    }
}