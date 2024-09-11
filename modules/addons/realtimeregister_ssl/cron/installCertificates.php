<?php

use AddonModule\RealtimeRegisterSsl\eServices\ManagementPanel\Deploy\Manage;
use WHMCS\Database\Capsule as DB;
use AddonModule\RealtimeRegisterSsl\models\orders\Repository as OrderRepo;
use AddonModule\RealtimeRegisterSsl\models\logs\Repository as LogsRepo;

define('DS', DIRECTORY_SEPARATOR);
define('WHMCS_MAIN_DIR', substr(dirname(__FILE__),0, strpos(dirname(__FILE__),'modules'.DS.'addons')));
define('ADDON_DIR', substr(dirname(__FILE__), 0, strpos(dirname(__FILE__), DS.'cron')));

require_once WHMCS_MAIN_DIR.DS.'init.php';

require_once ADDON_DIR.DS.'Loader.php';

$loader = new \AddonModule\RealtimeRegisterSsl\Loader();

$input = [];
$input['argv'] = $argv ?: $_SERVER['argv'];

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
            Manage::prepareDeploy($order->service_id, $details['domain'], $cert, $details['csr'], $key, $caBundle);
        }

        $logsRepo->addLog(
            $order->client_id,
            $order->service_id,
            'success',
            'The certificate for the '.$order->domain.' domain has been installed correctly.'
        );
        $orderRepo->updateStatus($order->service_id, 'Success');
    } catch (\Exception $e) {
        $logsRepo->addLog(
            $order->client_id,
            $order->service_id,
            'error',
            '['.$order->domain.'] Error: '.$e->getMessage()
        );
    }
}

die();
