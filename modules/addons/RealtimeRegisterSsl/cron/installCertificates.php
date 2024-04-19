<?php

use MGModule\RealtimeRegisterSsl\eModels\cpanelservices\Service;
use WHMCS\Database\Capsule as DB;
use MGModule\RealtimeRegisterSsl\eHelpers\Cpanel;
use MGModule\RealtimeRegisterSsl\models\orders\Repository as OrderRepo;
use MGModule\RealtimeRegisterSsl\models\logs\Repository as LogsRepo;

define('DS', DIRECTORY_SEPARATOR);
define('WHMCS_MAIN_DIR', substr(dirname(__FILE__),0, strpos(dirname(__FILE__),'modules'.DS.'addons')));
define('ADDON_DIR', substr(dirname(__FILE__), 0, strpos(dirname(__FILE__), DS.'cron')));

require_once WHMCS_MAIN_DIR.DS.'init.php';

require_once ADDON_DIR.DS.'Loader.php';
$loader = new \MGModule\RealtimeRegisterSsl\Loader();
$input = array();
$input['argv'] = $argv ? $argv : $_SERVER['argv'];

$logsRepo = new LogsRepo();

$orderRepo = new OrderRepo();
$orders = $orderRepo->getOrdersInstallation();

foreach ($orders as $order)
{
    $details = json_decode($order->configdata, true);

    $cert = $details['crt'];
    $cabundle = $details['ca'];
    $key = decrypt($details['private_key']);

    try {
        $service = new Service();
        $serviceCpanel = $service->getServiceByDomain($order->client_id, $order->domain);

        if ($serviceCpanel === false) {
            continue;
        }

        $cpanel = new Cpanel();
        $cpanel->setService($serviceCpanel);
        $cpanel->installSSL($serviceCpanel->user, $order->domain, $cert, $key, $cabundle);

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
        continue;

    }
}

die();

