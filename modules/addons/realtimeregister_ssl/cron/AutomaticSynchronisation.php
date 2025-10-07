<?php

namespace AddonModule\RealtimeRegisterSsl\cron;

use AddonModule\RealtimeRegisterSsl\Addon;
use AddonModule\RealtimeRegisterSsl\eHelpers\Whmcs;
use AddonModule\RealtimeRegisterSsl\eModels\whmcs\service\SSL;
use AddonModule\RealtimeRegisterSsl\eProviders\ApiProvider;
use AddonModule\RealtimeRegisterSsl\eRepository\whmcs\service\SSL as SSLRepo;
use AddonModule\RealtimeRegisterSsl\eServices\EmailTemplateService;
use DateTime;
use Illuminate\Database\Capsule\Manager as Capsule;
use RealtimeRegister\Api\CertificatesApi;
use RealtimeRegister\Api\ProcessesApi;
use RealtimeRegister\Domain\Certificate;
use RealtimeRegister\Domain\Enum\ProcessStatusEnum;
use WHMCS\Service\Service;

class AutomaticSynchronisation extends BaseTask
{
    protected $defaultFrequency = 60;
    protected $skipDailyCron = true;
    protected $defaultPriority = 4200;
    protected $successCountIdentifier = "synced";
    protected $successKeyword = "Certificate synchronized";
    protected $defaultName = "Certificate synchronization";

    public function __invoke()
    {
        if ($this->enabledTask('cron_synchronization')) {
            logActivity("Realtime Register SSL: Starting automatic synchronisation");
            Addon::I();

            $updatedServices = [];

            $this->sslRepo = new SSLRepo();

            //get all completed ssl orders
            $sslOrders = $this->getSSLOrders();

            foreach ($sslOrders as $sslService) {
                $serviceID = $sslService->serviceid;

                if (empty($sslService->remoteid)) {
                    continue;
                }

                $configdata = json_decode(json_encode($sslService->configdata), true);
                if (!empty($configdata['domain'])) {
                    Capsule::table('tblhosting')->where('id', $serviceID)->update(['domain' => $configdata['domain']]);
                }

                //if service is synchronized skip it
                if ($this->checkIfSynchronized($serviceID)) {
                    continue;
                }

                //set ssl certificate as synchronized
                $this->setSSLServiceAsSynchronized($serviceID);

                try {
                    /** @var ProcessesApi $processesApi */
                    $processesApi = ApiProvider::getInstance()->getApi(ProcessesApi::class);
                    $order = $processesApi->get($sslService->remoteid);
                } catch (\Exception $e) {
                    continue;
                }

                if ($order->status == ProcessStatusEnum::STATUS_FAILED || $order->status == ProcessStatusEnum::STATUS_CANCELLED) {
                    $sslService->setStatus(SSL::CANCELLED);
                    $updatedServices[] = $serviceID;
                    $sslService->save();
                    continue;
                }

                /** @var CertificatesApi $certificateApi */
                $certificateApi = ApiProvider::getInstance()->getApi(CertificatesApi::class);

                /** @var Certificate $sslOrder */
                $sslOrder = $certificateApi
                    ->listCertificates(1, null, null, ['process:eq' => $order->id])[0];

                //if certificate is active
                if ($sslOrder) {

                    //set ssl status as expired if expired
                    if ($sslOrder->expiryDate < new DateTime()) {
                        $sslService->setStatus(SSL::EXPIRED);
                    }

                    $updatedServices[] = $serviceID;
                } elseif ($order->status === ProcessStatusEnum::STATUS_SUSPENDED) {
                    $customerNotified = $sslService->getConfigdataKey('customer_notified');

                    /**
                     * If the status is suspended, we need some more data of the customer, so we send this person
                     * an email
                     */
                    if (!$customerNotified) {
                        sendMessage(
                            EmailTemplateService::VALIDATION_INFORMATION_TEMPLATE_ID,
                            $sslService->getServiceId(),
                            [
                                'domain' => $sslService->getDomain(),
                                'sslConfig' => $sslService->getConfigData()
                            ]
                        );

                        // We don't want to spam users all the time, just once is enough for now..
                        $sslService->setConfigdataKey('customer_notified', new DateTime());
                    }
                }
                $sslService->save();
            }
            logActivity('Realtime Register SSL: Synchronization completed.');

            Whmcs::savelogActivityRealtimeRegisterSsl(
                "Realtime Register SSL WHMCS: Synchronization completed. Number of synchronized services: " .
                count($updatedServices)
            );

            $this->output("synced")->write($updatedServices);
            return $this;
        }
    }

    private function checkIfSynchronized($serviceID): bool
    {
        $result = false;
        $sslService = $this->sslRepo->getByServiceId((int)$serviceID);

        $date = date('Y-m-d');
        $date = strtotime("-5 day", strtotime($date));

        if (strtotime($sslService->getConfigdataKey('synchronized')) > $date) {
            $result = true;
        }

        return $result;
    }

    private function setSSLServiceAsSynchronized($serviceID)
    {
        $sslService = $this->sslRepo->getByServiceId((int)$serviceID);
        $sslService->setConfigdataKey('synchronized', date('Y-m-d'));
        $sslService->save();
    }
}
