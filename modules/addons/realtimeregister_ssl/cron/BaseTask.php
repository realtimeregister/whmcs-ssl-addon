<?php

namespace AddonModule\RealtimeRegisterSsl\cron;

use AddonModule\RealtimeRegisterSsl\eHelpers\Whmcs;
use AddonModule\RealtimeRegisterSsl\eModels\whmcs\service\SSL;
use AddonModule\RealtimeRegisterSsl\eRepository\whmcs\service\SSL as SSLRepo;
use AddonModule\RealtimeRegisterSsl\eServices\provisioning\UpdateConfigData;
use AddonModule\RealtimeRegisterSsl\eServices\SSLOrderLock;
use AddonModule\RealtimeRegisterSsl\models\apiConfiguration\Repository;

class BaseTask extends \WHMCS\Scheduling\Task\AbstractTask
{
    protected const DEFAULT_STATUSES = [
        SSL::CONFIGURATION_SUBMITTED,
        SSL::PENDING_INSTALLATION,
        SSL::ACTIVE,
        SSL::FAILED_INSTALLATION
    ];

    /**
     * How long daily tasks wait for a locked order, as skipping it would cost a full day
     */
    protected const DAILY_LOCK_WAIT = 30;

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

    /**
     * @param array<int> $sslOrderIds
     */
    protected function checkOrdersStatus(
        array $sslOrderIds,
        array $status = self::DEFAULT_STATUSES,
        int $waitSeconds = 0
    ): void {
        foreach ($sslOrderIds as $sslOrderId) {
            $this->processSSLOrder(
                $sslOrderId,
                function (SSL $sslOrder) {
                    (new UpdateConfigData($sslOrder))->run();
                },
                $status,
                $waitSeconds
            );
        }
    }

    /**
     * Only returns the IDs, use getSSLOrder() to load an order right before processing it
     *
     * @return array<int>
     */
    protected function getSSLOrders(array $status = self::DEFAULT_STATUSES): array
    {
        return $this->sslRepo->getOrderIdsWithStatus($status);
    }

    /**
     * @return SSL|null null if the order doesn't exist (anymore) or no longer has one of the statuses
     */
    protected function getSSLOrder(int $sslOrderId, array $status = self::DEFAULT_STATUSES): ?SSL
    {
        return $this->sslRepo->getOrderWithStatus($sslOrderId, $status);
    }

    /**
     * Lock the order, load its current data and pass it to the callback
     */
    protected function processSSLOrder(
        int $sslOrderId,
        callable $callback,
        array $status = self::DEFAULT_STATUSES,
        int $waitSeconds = 0
    ): void {
        $this->lockSSLOrder(
            $sslOrderId,
            function () use ($sslOrderId, $callback, $status) {
                $sslOrder = $this->getSSLOrder($sslOrderId, $status);
                if ($sslOrder !== null) {
                    $callback($sslOrder);
                }
            },
            $waitSeconds
        );
    }

    /**
     * Run the callback while the order is locked. Orders locked by another process are skipped and errors are logged,
     * so a single order can't stop the task.
     */
    protected function lockSSLOrder(int $sslOrderId, callable $callback, int $waitSeconds = 0): void
    {
        try {
            $processed = SSLOrderLock::run($sslOrderId, $callback, $waitSeconds);
            if (!$processed && $waitSeconds > 0) {
                Whmcs::savelogActivityRealtimeRegisterSsl(
                    "Realtime Register SSL WHMCS task '" . $this->getName() . "': skipped SSL order " . $sslOrderId
                    . ", it is being processed by another task"
                );
            }
        } catch (\Throwable $e) {
            Whmcs::savelogActivityRealtimeRegisterSsl(
                "Realtime Register SSL WHMCS task error for task '" . $this->getName() . "' (SSL order "
                . $sslOrderId . "): " . $e->getMessage()
            );
        }
    }
}
