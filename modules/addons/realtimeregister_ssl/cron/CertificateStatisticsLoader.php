<?php

namespace AddonModule\RealtimeRegisterSsl\cron;

use AddonModule\RealtimeRegisterSsl\Addon;
use AddonModule\RealtimeRegisterSsl\eHelpers\Whmcs;
use AddonModule\RealtimeRegisterSsl\eProviders\ApiProvider;
use AddonModule\RealtimeRegisterSsl\eRepository\whmcs\service\SSL as SSLRepo;
use RealtimeRegister\Api\CertificatesApi;
use RealtimeRegister\Api\ProcessesApi;

class CertificateStatisticsLoader extends BaseTask
{
    protected $defaultFrequency = 240;
    protected $skipDailyCron = true;
    protected $defaultPriority = 4200;
    protected $defaultDescription = 'Load current SSL orders status';
    protected $defaultName = "Certificate statistics loader";

    public function __invoke()
    {
        if ($this->enabledTask('cron_ssl_summary_stats')) {
            logActivity("Realtime Register SSL: Certificate Stats Loader started");
            Addon::I();

            Whmcs::savelogActivityRealtimeRegisterSsl("Realtime Register SSL WHMCS: Certificate Stats Loader started.");

            $this->sslRepo = new SSLRepo();

            $services = new \AddonModule\RealtimeRegisterSsl\models\whmcs\service\Repository();
            $services->onlyStatus(['Active', 'Suspended']);

            foreach ($services->get() as $service) {
                $product = $service->product();
                //check if product is Realtime Register Ssl
                if ($product->serverType != 'realtimeregister_ssl') {
                    continue;
                }

                $SSLOrder = new \AddonModule\RealtimeRegisterSsl\eModels\whmcs\service\SSL();
                $ssl = $SSLOrder->getWhere(['serviceid' => $service->id, 'userid' => $service->clientID])->first();

                if ($ssl == null || $ssl->remoteid == '') {
                    continue;
                }
                /** @var ProcessesApi $processesApi */
                $processesApi = ApiProvider::getInstance()->getApi(ProcessesApi::class);
                $apiOrder = $processesApi->get($ssl->remoteid);

                /** @var CertificatesApi $certificatesApi */
                $certificatesApi = ApiProvider::getInstance()->getApi(CertificatesApi::class);

                $sslInformation = $certificatesApi->listCertificates(100, null, null, ['process:eq' => $ssl->remoteid]);

                if (count($sslInformation) === 1) {
                    $this->setSSLCertificateValidTillDate($service->id, $sslInformation[0]->expiryDate);
                }

                $this->setSSLCertificateStatus($service->id, $apiOrder->status);
            }
            Whmcs::savelogActivityRealtimeRegisterSsl(
                'Realtime Register SSL WHMCS: Certificate Stats Loader completed.'
            );
        }
    }

    private function setSSLCertificateValidTillDate($serviceID, $date)
    {
        $sslService = $this->sslRepo->getByServiceId((int)$serviceID);
        $sslService->setConfigdataKey('valid_till', $date);
        $sslService->save();
    }

    private function setSSLCertificateStatus($serviceID, $status)
    {
        $sslService = $this->sslRepo->getByServiceId((int)$serviceID);
        $sslService->setConfigdataKey('ssl_status', $status);
        $sslService->save();
    }
}
