<?php

namespace AddonModule\RealtimeRegisterSsl\eServices\provisioning;

use AddonModule\RealtimeRegisterSsl\eModels\whmcs\service\SSL;
use AddonModule\RealtimeRegisterSsl\eProviders\ApiProvider;
use AddonModule\RealtimeRegisterSsl\eRepository\whmcs\service\SSL as SSLRepo;
use AddonModule\RealtimeRegisterSsl\models\logs\Repository as LogsRepo;
use AddonModule\RealtimeRegisterSsl\models\whmcs\service\Service as Service;
use RealtimeRegister\Api\CertificatesApi;

class AdminImportCertificate extends Ajax
{
    private array $p;

    public function __construct(array $params)
    {
        $this->p = $params;
    }

    public function run(): void
    {
        $serviceId = $this->p['serviceId'];
        $certificateId = $this->p['certificateId'];

        if (!$certificateId) {
            $this->response(false, 'Certificate ID is required');
        }

        try {
            $sslRepo = new SSLRepo();
            $sslOrder = $sslRepo->getByServiceId($serviceId);

            if ($sslOrder->status !== SSL::AWAITING_CONFIGURATION) {
                $this->response(false, 'This action is only available for orders in Awaiting Configuration status.');
            }

            /** @var CertificatesApi $certificatesApi */
            $certificatesApi = ApiProvider::getInstance()->getApi(CertificatesApi::class);
            $certificate = $certificatesApi->getCertificate($certificateId);

            $remoteId = $certificate->process;

            if ($sslRepo->getByRemoteId($remoteId) != null) {
                $this->response(false, 'Active certificate already exists');
            }

            $sslOrder->setRemoteId($remoteId);
            $sslOrder->save();

            $updateConfigData = new UpdateConfigData($sslOrder);
            $updateConfigData->run();

            //update domain column in tblhosting
            $service = new Service($this->p['serviceId']);
            $service->save(['domain' => $certificate->domainName]);

            $this->response(true, 'Certicate successfully imported');
        } catch (\Exception $e) {
            (new LogsRepo())->addLog(
                $this->p['userID'] ?? 0,
                $serviceId,
                'error',
                'Import certificate failed: ' . $e->getMessage()
            );

            $this->response(false, $e->getMessage());
        }
    }
}
