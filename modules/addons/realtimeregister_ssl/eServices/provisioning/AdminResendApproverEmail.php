<?php

declare(strict_types=1);

namespace AddonModule\RealtimeRegisterSsl\eServices\provisioning;

use Exception;
use Illuminate\Database\Capsule\Manager as Capsule;
use AddonModule\RealtimeRegisterSsl\eProviders\ApiProvider;
use AddonModule\RealtimeRegisterSsl\eRepository\whmcs\service\SSL;
use AddonModule\RealtimeRegisterSsl\models\orders\Order;
use AddonModule\RealtimeRegisterSsl\models\orders\Repository;
use SandwaveIo\RealtimeRegister\Api\CertificatesApi;

class AdminResendApproverEmail extends Ajax
{
    private array $p;

    public function __construct(array $params)
    {
        $this->p = &$params;
    }

    public function run()
    {
        try {
            $this->adminResendApproverEmail();
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
        return 'success';
    }

    private function adminResendApproverEmail(): void
    {
        $ssl = new SSL();
        $serviceSSL = $ssl->getByServiceId($this->p['serviceid']);

        $orderRepository = new Repository();
        if (is_null($serviceSSL)) {
            throw new Exception('Create has not been initialized.');
        }

        /** @var CertificatesApi $certificatesApi */
        $certificatesApi = ApiProvider::getInstance()->getApi(CertificatesApi::class);

        /** @var Order $result */
        $result = Capsule::table($orderRepository->tableName)->where('service_id', $serviceSSL->serviceid)->first();
        $data = json_decode((string)$result->data, true);

        $domainControlValidations = [];
        foreach ($data['validations']['dcv'] as $validation) {
            $domainControlValidations[] = [
                'commonName' => $validation['commonName'],
                'type' => (strtoupper($validation['type']) === 'HTTP' ? 'FILE' : strtoupper($validation['type'])),
                'email' => $validation['email'],
            ];
        }
        $certificatesApi->resendDcv(
            (int)$serviceSSL->getRemoteId(),
            $domainControlValidations
        );
    }
}
