<?php

namespace AddonModule\RealtimeRegisterSsl\eServices\provisioning;

use AddonModule\RealtimeRegisterSsl\eRepository\whmcs\service\SSL;
use Exception;
use AddonModule\RealtimeRegisterSsl\eProviders\ApiProvider;
use RealtimeRegister\Api\CertificatesApi;

class TerminateAccount
{
    private $p;

    function __construct(&$params)
    {
        $this->p = &$params;
    }

    public function run()
    {
        try {
            $this->terminateAccount();
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
        return 'success';
    }

    /**
     * @throws Exception
     */
    private function terminateAccount(): void
    {
        $ssl = new SSL();
        $serviceSSL = $ssl->getByServiceId($this->p['serviceid']);
        
        if (is_null($serviceSSL)) {
            throw new Exception('Create has not been initialized.');
        }
        
        if (empty($serviceSSL->remoteid) || $serviceSSL->getSSLStatus() == 'FAILED') {
            $serviceSSL->delete();
            return;
        }

        /** @var CertificatesApi $certficatesApi */
        $certficatesApi = ApiProvider::getInstance()->getApi(CertificatesApi::class);
        $certficatesApi->revokeCertificate($serviceSSL->getCertificateId());
        $serviceSSL->delete();
    }
}
