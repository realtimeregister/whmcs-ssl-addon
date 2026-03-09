<?php

declare(strict_types=1);

namespace AddonModule\RealtimeRegisterSsl\eServices\provisioning;

use AddonModule\RealtimeRegisterSsl\eProviders\ApiProvider;
use AddonModule\RealtimeRegisterSsl\eRepository\whmcs\service\SSL as SSLRepo;
use AddonModule\RealtimeRegisterSsl\models\orders\Order;
use AddonModule\RealtimeRegisterSsl\models\orders\Repository;
use Exception;
use Illuminate\Database\Capsule\Manager as Capsule;
use RealtimeRegister\Api\CertificatesApi;
use RealtimeRegister\Domain\ResendDcvCollection;

class AdminResendDCV extends Ajax
{
    private array $p;

    public function __construct(array $params)
    {
        $this->p = &$params;
    }

    public function run()
    {
        try {
            $this->resendDcv();
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
        return 'success';
    }

    private function resendDcv(): void
    {
        $sslRepo = new SSLRepo();
        $sslService = $sslRepo->getByServiceId($this->p['serviceid']);

        /** @var CertificatesApi $certificatesApi */
        $certificatesApi = ApiProvider::getInstance()->getApi(CertificatesApi::class);
        $configData = $sslService->configdata;


        $domainControlValidations = [];
        foreach ($configData->validations->dcv ?? [] as $validation) {
            $domainControlValidations[] = [
                'commonName' => $validation->commonName,
                'type' => (strtoupper($validation->type) === 'HTTP' ? 'FILE' : strtoupper($validation->type)),
                'email' => $validation->email
            ];
        }
        $certificatesApi->resendDcv(
            (int) $sslService->getRemoteId(),
            ResendDcvCollection::fromArray($domainControlValidations)
        );
    }
}
