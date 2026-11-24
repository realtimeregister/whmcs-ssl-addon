<?php

namespace AddonModule\RealtimeRegisterSsl\cron;

use AddonModule\RealtimeRegisterSsl\Addon;
use AddonModule\RealtimeRegisterSsl\eModels\whmcs\service\SSL;
use AddonModule\RealtimeRegisterSsl\eRepository\whmcs\service\SSL as SSLRepo;
use AddonModule\RealtimeRegisterSsl\eServices\ManagementPanel\Deploy\Manage;
use AddonModule\RealtimeRegisterSsl\eServices\provisioning\SSLUtils;
use AddonModule\RealtimeRegisterSsl\models\logs\Repository as LogsRepo;
use AddonModule\RealtimeRegisterSsl\models\orders\Repository as OrderRepo;

class InstallCertificates extends BaseTask
{
    protected $skipDailyCron = false;
    protected $defaultPriority = 28800;
    protected $defaultName = "Install certificates";
    protected $maxFailureNumber = 5;

    use SSLUtils;

    public function __invoke()
    {
        if ($this->enabledTask('cron_certificate_installer')) {
            logActivity("Realtime Register SSL: Install certificates");
            Addon::I();

            $this->sslRepo = new SSLRepo();
            $logsRepo = new LogsRepo();

            $orderRepo = new OrderRepo();
            $orders = $orderRepo->getOrdersInstallation();

            foreach ($orders as $order) {
                $this->processSSLOrder(
                    $order->tblsslorder_id,
                    function (SSL $sslOrder) use ($order, $logsRepo, $orderRepo) {
                        $this->install($sslOrder, $order, $logsRepo, $orderRepo);
                    },
                    [SSL::PENDING_INSTALLATION],
                    self::DAILY_LOCK_WAIT
                );
            }
        }
    }

    private function install(SSL $sslOrder, $order, LogsRepo $logsRepo, OrderRepo $orderRepo): void
    {
        $details = $sslOrder->getConfigData();
        if (!$details) {
            return;
        }

        $tries = $details['tries_to_install'] ?? 0;

        // We don't want to continue trying installing the certificate if it has been tried more than 5 times
        if ($tries >= $this->maxFailureNumber) {
            $sslOrder->setStatus(SSL::FAILED_INSTALLATION);
            $sslOrder->save();

            $logsRepo->addLog(
                $order->client_id,
                $order->service_id,
                'error',
                '[' . $order->domain . '] We have stopped trying to install the certificate after '
                . $this->maxFailureNumber . ' failures '
            );
            return;
        }

        $cert = $details['crt'];
        $caBundle = $details['ca'];
        $key = decrypt($details['private_key']);
        if (!$details['domain']) {
            return;
        }
        $panelData = $this->getPanelData($details['domain']);
        if (!$panelData) {
            return;
        }

        try {
            $manage = new Manage($panelData['panel']);
            $manage->prepareDeploy(
                $details['domain'],
                $cert,
                $details['csr'],
                $key,
                $caBundle
            );

            $logsRepo->addLog(
                $order->client_id,
                $order->service_id,
                'success',
                'The certificate for the ' . $order->domain . ' domain has been installed correctly.'
            );
            $orderRepo->updateStatus($order->service_id, 'Success');
        } catch (\Exception $e) {
            $logsRepo->addLog(
                $order->client_id,
                $order->service_id,
                'error',
                '[' . $order->domain . '] Error: ' . $e->getMessage()
            );

            // Update the number of tries, so we can stop trying after $maxFailureNumber
            $sslOrder->setConfigdataKey('tries_to_install', $tries + 1);
            $sslOrder->save();
        }
    }
}
