<?php

namespace AddonModule\RealtimeRegisterSsl\cron;

use AddonModule\RealtimeRegisterSsl\eHelpers\Whmcs;
use AddonModule\RealtimeRegisterSsl\eServices\provisioning\UpdateConfigs;
use AddonModule\RealtimeRegisterSsl\models\apiConfiguration\Repository;

class BaseTask extends \WHMCS\Scheduling\Task\AbstractTask
{
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

    protected function getSSLOrders($serviceID = null)
    {
        $where = [
            'status' => 'Completed',
            'module' => 'realtimeregister_ssl'
        ];

        if ($serviceID !== null) {
            $where['serviceid'] = $serviceID;
        }

        return $this->sslRepo->getBy($where, true);
    }
}