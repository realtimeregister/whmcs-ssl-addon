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
            foreach ($this->getSSLOrders() as $sslOrderId) {
                $this->processSSLOrder($sslOrderId, function (SSL $sslService) use (&$updatedServices) {
                    if ($this->synchronize($sslService)) {
                        $updatedServices[] = $sslService->serviceid;
                    }
                });
            }

            logActivity('Realtime Register SSL: Synchronization completed.');

            Whmcs::savelogActivityRealtimeRegisterSsl(
                "Realtime Register SSL WHMCS: Synchronization completed. Number of synchronized services: " .
                count($updatedServices)
            );

            $this->output("synced")->write($updatedServices);
        }

        return $this;
    }

    /**
     * @return bool true if the service has been updated
     */
    private function synchronize(SSL $sslService): bool
    {
        if (empty($sslService->remoteid)) {
            return false;
        }

        if ($sslService->isAcmeProduct()) {
            // TODO
            return false;
        }

        $domain = $sslService->getDomain();
        if (!empty($domain)) {
            Capsule::table('tblhosting')->where('id', $sslService->serviceid)->update(['domain' => $domain]);
        }

        //if service is synchronized skip it
        if ($this->checkIfSynchronized($sslService)) {
            return false;
        }

        //set ssl certificate as synchronized
        $sslService->setConfigdataKey('synchronized', date('Y-m-d'));

        try {
            /** @var ProcessesApi $processesApi */
            $processesApi = ApiProvider::getInstance()->getApi(ProcessesApi::class);
            $order = $processesApi->get($sslService->remoteid);
        } catch (\Exception $e) {
            return false;
        }

        if ($order->status == ProcessStatusEnum::STATUS_FAILED || $order->status == ProcessStatusEnum::STATUS_CANCELLED) {
            $sslService->setStatus(SSL::CANCELLED);
            $sslService->save();
            return true;
        }

        /** @var CertificatesApi $certificateApi */
        $certificateApi = ApiProvider::getInstance()->getApi(CertificatesApi::class);

        /** @var Certificate $certificate */
        $certificate = $certificateApi
            ->listCertificates(1, null, null, ['process:eq' => $order->id])[0] ?? null;

        //if certificate is active
        if ($certificate) {
            //set ssl status as expired if expired
            if ($certificate->expiryDate < new DateTime()) {
                $sslService->setStatus(SSL::EXPIRED);
            }

            $sslService->save();
            return true;
        }

        $notifyCustomer = $order->status === ProcessStatusEnum::STATUS_SUSPENDED
            && !$sslService->getConfigdataKey('customer_notified');

        if ($notifyCustomer) {
            // We don't want to spam users all the time, just once is enough for now. This is saved before sending the
            // email, so an error while sending can't result in the customer getting the same email over and over.
            $sslService->setConfigdataKey('customer_notified', new DateTime());
        }

        $sslService->save();

        if ($notifyCustomer) {
            $this->sendValidationInformation($sslService);
        }

        return false;
    }

    /**
     * If the status is suspended, we need some more data of the customer, so we send this person an email
     */
    private function sendValidationInformation(SSL $sslService): void
    {
        //TODO acme subscripions OV template
        $result = sendMessage(
            EmailTemplateService::VALIDATION_INFORMATION_TEMPLATE_ID,
            $sslService->getServiceId(),
            [
                'domain' => $sslService->getDomain(),
                'sslConfig' => $sslService->getConfigData()
            ]
        );

        if ($result !== true) {
            Whmcs::savelogActivityRealtimeRegisterSsl(
                'Realtime Register SSL WHMCS: Unable to send validation information (service '
                . $sslService->getServiceId() . ')' . (is_string($result) ? ': ' . $result : '')
            );
        }
    }

    private function checkIfSynchronized(SSL $sslService): bool
    {
        $date = date('Y-m-d');
        $date = strtotime("-5 day", strtotime($date));

        if (strtotime($sslService->getConfigdataKey('synchronized')) > $date) {
            return true;
        }

        return false;
    }
}
