<?php

namespace MGModule\RealtimeRegisterSsl\eServices\provisioning;

use Exception;
use MGModule\RealtimeRegisterSsl\eProviders\ApiProvider;
use SandwaveIo\RealtimeRegister\Api\ProcessesApi;

class AdminViewCertyfifcate extends Ajax {

    private $p;

    function __construct(&$params) {
        $this->p = &$params;

    }

    public function run() {
        try {
            $this->viewCertyfifcate();
        } catch (Exception $ex) {
            $this->response(false, $ex->getMessage());
        }
    }

    private function viewCertyfifcate() {
        $sslRepo    = new \MGModule\RealtimeRegisterSsl\eRepository\whmcs\service\SSL();
        $sslService = $sslRepo->getByServiceId($this->p['serviceId']);

        if (is_null($sslService)) {
            throw new Exception('Create has not been initialized');
        }

        if ($this->p['userID'] != $sslService->userid) {
            throw new Exception('An error occurred');
        }

        /** @var ProcessesApi $processesApi */
        $processesApi = ApiProvider::getInstance()->getApi(ProcessesApi::class);
        $orderStatus = $processesApi->get($sslService->remoteid);

        $return = [];
       
        if (!empty($orderStatus['csr_code'])) {
            $return['csr'] = $orderStatus['csr_code'];
        }

        if (!empty($orderStatus['crt_code'])) {
            $return['crt'] = $orderStatus['crt_code'];
        }
        

        if (!empty($orderStatus['ca_code'])) {
            $return['ca'] = $orderStatus['ca_code'];
        }
        
        if ($orderStatus['status'] !== 'active') {
            $this->response(false, 'Order status is not active, so can not display certificate', $return);
        } else {
            $this->response(true, 'Details', $return);
        }
    }
}
