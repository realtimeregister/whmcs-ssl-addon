<?php

namespace AddonModule\RealtimeRegisterSsl\cron;

use AddonModule\RealtimeRegisterSsl\eHelpers\Whmcs;
use AddonModule\RealtimeRegisterSsl\eModels\whmcs\service\SSL;
use AddonModule\RealtimeRegisterSsl\eProviders\ApiProvider;
use AddonModule\RealtimeRegisterSsl\eRepository\whmcs\service\SSL as SSLRepo;
use AddonModule\RealtimeRegisterSsl\eServices\EmailTemplateService;
use RealtimeRegister\Api\CertificatesApi;
use RealtimeRegister\Api\ProcessesApi;

class CertificateSender extends BaseTask
{
    protected $defaultFrequency = 180;
    protected $skipDailyCron = true;
    protected $defaultPriority = 4200;
    protected $defaultName = "Certificate Sender started";
    protected $successCountIdentifier = "messages";

    public function __invoke()
    {
        if ($this->enabledTask('cron_send_certificate')) {
            logActivity("Realtime Register SSL: Certificate Sender started");

            Whmcs::savelogActivityRealtimeRegisterSsl("Realtime Register SSL WHMCS: Certificate Sender started.");

            $emailSendsCount = 0;
            $this->sslRepo = new SSLRepo();

            $services = new \AddonModule\RealtimeRegisterSsl\models\whmcs\service\Repository();
            $services->onlyStatus(['Active']);

            foreach ($services->get() as $service) {
                $product = $service->product();
                //check if product is Realtime Register Ssl
                if ($product->serverType != 'realtimeregister_ssl') {
                    continue;
                }

                $SSLOrder = new \AddonModule\RealtimeRegisterSsl\eModels\whmcs\service\SSL();
                /** @var SSL $ssl */
                $ssl = $SSLOrder->getWhere(['serviceid' => $service->id, 'userid' => $service->clientID])->first();

                if ($ssl == null || $ssl->remoteid == '') {
                    continue;
                }
                /** @var ProcessesApi $processesApi */
                $processesApi = ApiProvider::getInstance()->getApi(ProcessesApi::class);
                $apiOrder = $processesApi->get($ssl->remoteid);

                /** @var CertificatesApi $certificateApi */
                $certificateApi = ApiProvider::getInstance()->getApi(CertificatesApi::class);
                $certificateInfo = $certificateApi->listCertificates(1, null, null, ['process:eq' => $ssl->remoteid]);

                if ($apiOrder->status !== 'COMPLETED' || empty($certificateInfo[0]->certificate)) {
                    continue;
                }

                if ($this->checkIfCertificateSent($service->id)) {
                    continue;
                }

                $apiConf = (new \AddonModule\RealtimeRegisterSsl\models\apiConfiguration\Repository())->get();

                $sendCertificateTemplate = $apiConf->send_certificate_template;
                if ($sendCertificateTemplate == null) {
                    sendMessage(EmailTemplateService::SEND_CERTIFICATE_TEMPLATE_ID, $service->id, [
                        'domain' => $certificateInfo[0]->domainName,
                        'ssl_certificate' => nl2br($certificateInfo[0]->certificate),
                        'crt_code' => null,
                    ]);
                } else {
                    $templateName = EmailTemplateService::getTemplateName($sendCertificateTemplate);
                    sendMessage($templateName, $service->id, [
                        'domain' => $certificateInfo[0]->domainName,
                        'ssl_certificate' => nl2br($certificateInfo[0]->certificate),
                        'crt_code' => null,
                    ]);
                }
                $this->setSSLCertificateAsSent($service->id);
                $emailSendsCount++;
            }

            $this->output("messages")->write($emailSendsCount);

            Whmcs::savelogActivityRealtimeRegisterSsl(
                'Realtime Register SSL WHMCS: Certificate Sender completed. The number of messages sent: '
                . $emailSendsCount
            );
            return $this;
        }
    }

    private function checkIfCertificateSent($serviceID): bool
    {
        $result = false;
        if ($this->sslRepo === null) {
            $this->sslRepo = new SSLRepo();
        }

        $sslService = $this->sslRepo->getByServiceId((int)$serviceID);
        if ($sslService->getConfigdataKey('certificateSent')) {
            $result = true;
        }

        return $result;
    }

    private function setSSLCertificateAsSent($serviceID)
    {
        if ($this->sslRepo === null) {
            $this->sslRepo = new SSLRepo();
        }
        $sslService = $this->sslRepo->getByServiceId((int)$serviceID);
        $sslService->setConfigdataKey('certificateSent', true);
        $sslService->save();
    }
}
