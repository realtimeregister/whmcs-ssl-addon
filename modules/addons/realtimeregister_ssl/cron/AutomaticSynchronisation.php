<?php

namespace AddonModule\RealtimeRegisterSsl\cron;

use AddonModule\RealtimeRegisterSsl\eHelpers\Whmcs;
use AddonModule\RealtimeRegisterSsl\eProviders\ApiProvider;
use AddonModule\RealtimeRegisterSsl\eRepository\whmcs\service\SSL as SSLRepo;
use AddonModule\RealtimeRegisterSsl\eServices\EmailTemplateService;
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

            $updatedServices = [];

            $this->sslRepo = new SSLRepo();

            //get all completed ssl orders
            $sslOrders = $this->getSSLOrders();

            foreach ($sslOrders as $sslService) {
                $serviceID = $sslService->serviceid;

                if (!isset($sslService->remoteid) || empty($sslService->remoteid)) {
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
                } catch (Exception $e) {
                    continue;
                }

                $service = (array)Capsule::table('tblhosting')->where('id', $serviceID)->first();
                $product = (array)Capsule::table('tblproducts')->where('servertype', 'realtimeregister_ssl')
                    ->where('id', $service['packageid'])->first();

                if (
                    isset($product['configoption7']) && !empty($product['configoption7'])
                    && $service['billingcycle'] == 'One Time'
                ) {
                    Capsule::table('tblhosting')->where('id', $serviceID)
                        ->update(['termination_date' => $order['valid_till']]);
                }

                if ($order->status == 'expired' || $order->status == 'cancelled') {
                    $this->setSSLServiceAsTerminated($serviceID);
                    $updatedServices[] = $serviceID;
                }

                /** @var CertificatesApi $certificateApi */
                $certificateApi = ApiProvider::getInstance()->getApi(CertificatesApi::class);

                /** @var Certificate $sslOrder */
                $sslOrder = $certificateApi->listCertificates(1, null, null, ['process:eq' => $order->remoteid])[0];

                //if certificate is active
                if ($sslOrder) {
                    //update whmcs service next due date
                    $newNextDueDate = $sslOrder->expiryDate;
                    if ($sslOrder->subscriptionEndDate) {
                        $newNextDueDate = $sslOrder->subscriptionEndDate;
                    }

                    //set ssl certificate as terminated if expired
                    if ($sslOrder->expiryDate->getTimestamp() < date('Y-m-d')) {
                        $this->setSSLServiceAsTerminated($serviceID);
                    }

                    $this->updateServiceNextDueDate($serviceID, $newNextDueDate);

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
                        $sslService->setConfigdataKey('customer_notified', new \DateTime());
                        $sslService->save();
                    }
                }
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

    private function setSSLServiceAsTerminated($serviceID)
    {
        $service = Service::find($serviceID);
        if (!empty($service)) {
            $service->status = 'terminated';
            $service->save();

            Whmcs::savelogActivityRealtimeRegisterSsl(
                "Realtime Register SSL WHMCS: Service #$serviceID set as Terminated"
            );
        }
    }

    private function checkServiceBillingPeriod($serviceID): bool
    {
        $skipPeriods = ['Monthly', 'One Time', 'Free Account'];
        $skip = false;
        $service = Service::find($serviceID);

        if (in_array($service->billingcycle, $skipPeriods) || $service == null) {
            $skip = true;
        }

        return $skip;
    }


    private function updateServiceNextDueDate($serviceID, $date)
    {
        $service = Service::find($serviceID);
        if (!empty($service)) {
            $createInvoiceDaysBefore = Capsule::table("tblconfiguration")
                ->where('setting', 'CreateInvoiceDaysBefore')->first();
            $service->nextduedate = $date;
            $nextinvoicedate = date('Y-m-d', strtotime("-{$createInvoiceDaysBefore->value} day", strtotime($date)));
            $service->nextinvoicedate = $nextinvoicedate;
            $service->save();

            Whmcs::savelogActivityRealtimeRegisterSsl(
                "Realtime Register SSL WHMCS: Service #" . $serviceID . " nextduedate set to "
                . $date . " and nextinvoicedate to" . $nextinvoicedate
            );
        }
    }
}
