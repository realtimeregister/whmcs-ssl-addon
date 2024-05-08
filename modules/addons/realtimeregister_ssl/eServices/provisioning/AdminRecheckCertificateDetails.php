<?php

declare(strict_types=1);

namespace MGModule\RealtimeRegisterSsl\eServices\provisioning;

use Exception;
use SandwaveIo\RealtimeRegister\Domain\Certificate;

class AdminRecheckCertificateDetails extends Ajax
{
    private $parameters;

    function __construct(&$params)
    {
        $this->parameters = &$params;
    }

    public function run()
    {
        try {
            $this->getCertificateDetails();
        } catch (Exception $ex) {
            $this->response(false, $ex->getMessage());
        }
    }

    private function getCertificateDetails()
    {
        $sslRepo = new \MGModule\RealtimeRegisterSsl\eRepository\whmcs\service\SSL();
        $sslService = $sslRepo->getByServiceId($this->parameters['serviceId']);

        if (is_null($sslService)) {
            throw new Exception('Create has not been initialized');
        }

        if ($this->parameters['userID'] != $sslService->userid) {
            throw new Exception('An error occurred');
        }
   
        $configDataUpdate = new \MGModule\RealtimeRegisterSsl\eServices\provisioning\UpdateConfigData($sslService);
        /** @var Certificate $orderStatus */
        $orderStatus = $configDataUpdate->run();

        $return = [];

        $return['RealtimeRegisterSsl API Order ID'] = $sslService->id;
        $return['Comodo Order ID'] = $orderStatus->providerId?:"-";
        $return['Configuration Status'] = $sslService->getSSLStatus();
        $return['Domain'] = $orderStatus->domainName;
        $return['Order Status'] = ucfirst($orderStatus->status);
        $return['Order Status Description'] = '-';
        
        if ($orderStatus->status == 'ACTIVE')
        {
            $return['Valid From'] = $orderStatus->startDate->format('Y-m-d h:i:s');
            $return['Expires'] = $orderStatus->expiryDate->format('Y-m-d h:i:s');
        }
        
        foreach ($orderStatus->san as $key => $san) {
            $return['SAN ' . ($key + 1)] = sprintf('%s / %s', $san['san_name'], $san['status_description']);
        }
            
        $this->response(true, 'Details', $return);
    }
}
