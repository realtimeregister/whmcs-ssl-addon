<?php

declare(strict_types=1);

namespace MGModule\RealtimeRegisterSsl\eServices\provisioning;

use Exception;

class AdminViewCertificate extends Ajax
{
    private $p;

    function __construct(&$params)
    {
        $this->p = &$params;
    }

    public function run()
    {
        try {
            $this->viewCertificate();
        } catch (Exception $ex) {
            $this->response(false, $ex->getMessage());
        }
    }

    private function viewCertificate()
    {
        $sslRepo = new \MGModule\RealtimeRegisterSsl\eRepository\whmcs\service\SSL();
        $sslService = $sslRepo->getByServiceId($this->p['serviceId']);

        if (is_null($sslService)) {
            throw new Exception('Create has not been initialized');
        }

        if ($this->p['userID'] != $sslService->userid) {
            throw new Exception('An error occurred');
        }

        $return = [];
        if (!empty($sslService->getCsr())) {
            $return['csr'] = $sslService->getCsr();
        }

        if (!empty($sslService->getCa())) {
            $return['ca'] = $sslService->getCa();
        }

        if (empty($sslService->getCrt())) {
            $this->response(false, 'Order status is not active, so can not display certificate', $return);
            return;
        }

        $return['crt'] = $sslService->getCrt();

        $this->response(true, 'Details', $return);
    }
}
