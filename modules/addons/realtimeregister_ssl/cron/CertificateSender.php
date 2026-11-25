<?php

namespace AddonModule\RealtimeRegisterSsl\cron;

use AddonModule\RealtimeRegisterSsl\Addon;
use AddonModule\RealtimeRegisterSsl\eHelpers\Whmcs;
use AddonModule\RealtimeRegisterSsl\eModels\whmcs\service\SSL;
use AddonModule\RealtimeRegisterSsl\eProviders\ApiProvider;
use AddonModule\RealtimeRegisterSsl\eServices\EmailTemplateService;
use AddonModule\RealtimeRegisterSsl\models\apiConfiguration\Repository as ApiConfigRepo;
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
            Addon::I();

            Whmcs::savelogActivityRealtimeRegisterSsl("Realtime Register SSL WHMCS: Certificate Sender started.");

            $emailSendsCount = 0;

            $services = new \AddonModule\RealtimeRegisterSsl\models\whmcs\service\Repository();
            $services->onlyStatus(['Active']);

            foreach ($services->get() as $service) {
                $product = $service->product();
                //check if product is Realtime Register Ssl
                if ($product->serverType != 'realtimeregister_ssl') {
                    continue;
                }

                $sslOrderId = SSL::query()
                    ->where('serviceid', $service->id)
                    ->where('userid', $service->clientID)
                    ->value('id');

                if ($sslOrderId === null) {
                    continue;
                }

                $this->lockSSLOrder($sslOrderId, function () use ($sslOrderId, &$emailSendsCount) {
                    $ssl = SSL::query()->find($sslOrderId);
                    if ($ssl !== null && $this->sendCertificate($ssl)) {
                        $emailSendsCount++;
                    }
                });
            }

            $this->output("messages")->write($emailSendsCount);

            Whmcs::savelogActivityRealtimeRegisterSsl(
                'Realtime Register SSL WHMCS: Certificate Sender completed. The number of messages sent: '
                . $emailSendsCount
            );
            return $this;
        }
    }

    /**
     * @return bool true if the certificate has been sent
     */
    private function sendCertificate(SSL $ssl): bool
    {
        if (!$ssl->getRemoteId() || $ssl->isAcmeProduct() || $ssl->getConfigdataKey('certificateSent')) {
            return false;
        }

        /** @var ProcessesApi $processesApi */
        $processesApi = ApiProvider::getInstance()->getApi(ProcessesApi::class);
        $apiOrder = $processesApi->get($ssl->getRemoteId());

        /** @var CertificatesApi $certificateApi */
        $certificateApi = ApiProvider::getInstance()->getApi(CertificatesApi::class);
        $certificateInfo = $certificateApi->listCertificates(1, null, null, ['process:eq' => $ssl->getRemoteId()]);

        if ($apiOrder->status !== 'COMPLETED' || empty($certificateInfo[0]->certificate)) {
            return false;
        }

        $apiConf = (new ApiConfigRepo())->get();

        $sendCertificateTemplate = $apiConf->send_certificate_template;
        if ($sendCertificateTemplate == null) {
            $templateName = EmailTemplateService::SEND_CERTIFICATE_TEMPLATE_ID;
        } else {
            $templateName = EmailTemplateService::getTemplateName($sendCertificateTemplate);
        }

        sendMessage($templateName, $ssl->getServiceId(), [
            'domain' => $certificateInfo[0]->domainName,
            'ssl_certificate' => nl2br($certificateInfo[0]->certificate),
            'crt_code' => null,
        ]);

        $ssl->setCertificateSent(true);
        $ssl->save();

        return true;
    }
}
