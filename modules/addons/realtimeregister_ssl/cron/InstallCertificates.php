<?php

namespace AddonModule\RealtimeRegisterSsl\cron;

use AddonModule\RealtimeRegisterSsl\Addon;
use AddonModule\RealtimeRegisterSsl\eModels\whmcs\service\SSL;
use AddonModule\RealtimeRegisterSsl\eServices\ManagementPanel\Deploy\Manage;
use AddonModule\RealtimeRegisterSsl\models\logs\Repository as LogsRepo;
use AddonModule\RealtimeRegisterSsl\models\orders\Repository as OrderRepo;
use Illuminate\Database\Capsule\Manager;

class InstallCertificates extends BaseTask
{
    protected $skipDailyCron = false;
    protected $defaultPriority = 28800;
    protected $defaultName = "Install certificates";
    protected $maxFailureNumber = 5;

    public function __invoke()
    {
        if ($this->enabledTask('cron_certificate_installer')) {
            logActivity("Realtime Register SSL: Install certificatesz");
            Addon::I();

            $logsRepo = new LogsRepo();

            $orderRepo = new OrderRepo();
            $orders = $orderRepo->getOrdersInstallation();

            foreach ($orders as $order) {
                $details = json_decode($order->configdata, true);
                // We don't want to continue trying installing the certificate if it has been tried more than 5 times
                if (
                    array_key_exists('tries_to_install', $details)
                    && $details['tries_to_install'] >= $this->maxFailureNumber
                ) {
                    $sslOrder = SSL::getWhere(['serviceid' => $order->service_id])->first();
                    Manager::table(SSL::TABLE_NAME)->where('id', $sslOrder->id)->update(
                        ['status' => SSL::FAILED_INSTALLATION]
                    );

                    $logsRepo->addLog(
                        $order->client_id,
                        $order->service_id,
                        'error',
                        '[' . $order->domain . '] We have stopped trying to install the certificate after '
                        . $this->maxFailureNumber . ' failures '
                    );
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
                    // Update the number of tries, so we can stop trying after $maxFailureNumber
                    if (!array_key_exists('tries_to_install', $details)) {
                        $details['tries_to_install'] = 1;
                    } else {
                        $details['tries_to_install']++;
                    }

                    $sslOrder = SSL::getWhere(['serviceid' => $order->service_id])->first();
                    Manager::table(SSL::TABLE_NAME)->where('id', $sslOrder->id)->update(
                        ['configdata' => json_encode($details)]
                    );
                }
            }
        }
    }
}
