<?php

namespace AddonModule\RealtimeRegisterSsl\cron;

use AddonModule\RealtimeRegisterSsl\Addon;
use AddonModule\RealtimeRegisterSsl\eModels\whmcs\service\SSL;
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
            Addon::I();

            $logsRepo = new LogsRepo();

            $orderRepo = new OrderRepo();
            $orders = $orderRepo->getOrdersInstallation();

            foreach ($orders as $order) {
                $details = json_decode($order->configdata, true);
                // We don't want to continue trying installing the certificate if it has been tried more than 5 times
                if (array_key_exists('tries_to_install', $details) && $details['tries_to_install'] >= 5) {
                    $orderRepo->updateStatus($order->service_id, SSL::FAILED_INSTALLATION);
                    continue;
                }
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

                    if (!array_key_exists('tries_to_install', $details)) {
                        $details['tries_to_install'] = 1;
                    } else {
                        $details['tries_to_install']++;
                    }

                    $order->setConfigdataAttribute($details);
                    $order->save();
                }
            }
        }
    }
}
