<?php

namespace AddonModule\RealtimeRegisterSsl\cron;

use AddonModule\RealtimeRegisterSsl\eServices\ManagementPanel\Deploy\Manage;
use AddonModule\RealtimeRegisterSsl\models\logs\Repository as LogsRepo;
use AddonModule\RealtimeRegisterSsl\models\orders\Repository as OrderRepo;

class InstallCertificates extends BaseTask
{
    protected $skipDailyCron = false;
    protected $defaultPriority = 28800;
    protected $defaultName = "Install certificates";

    public function __invoke()
    {
        if ($this->enabledTask('cron_certificate_installer')) {
            logActivity("Realtime Register SSL: Install certificates");

            $logsRepo = new LogsRepo();

            $orderRepo = new OrderRepo();
            $orders = $orderRepo->getOrdersInstallation();
            foreach ($orders as $order) {
                $details = json_decode($order->configdata, true);
                $cert = $details['crt'];
                $caBundle = $details['ca'];
                $key = decrypt($details['private_key']);

                try {
                    if ($details['domain']) {
                        Manage::prepareDeploy(
                            $order->service_id,
                            $details['domain'],
                            $cert,
                            $details['csr'],
                            $key,
                            $caBundle
                        );
                    }

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
                }
            }
        }
    }
}
