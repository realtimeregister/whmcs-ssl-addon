<?php

namespace MGModule\RealtimeRegisterSsl\eServices\provisioning;

use Exception;

class AdminResendCertificate {

    private $p;
    
    function __construct(&$params) {
        $this->p = &$params;
    }

    public function run() {
        try {
            $this->adminResendCertificate();
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
        return 'success';
    }
    
    private function adminResendCertificate() {
        $ssl = new \MGModule\RealtimeRegisterSsl\eRepository\whmcs\service\SSL();
        $serviceSSL = $ssl->getByServiceId($this->p['serviceid']);
        
        if (is_null($serviceSSL)) {
            throw new Exception('Create has not been initialized.');
        }
        
        if(empty($serviceSSL->remoteid)) {
            throw new Exception('Product not ordered in RealtimeRegisterSsl.');
        }
        
        $orderStatus = \MGModule\RealtimeRegisterSsl\eProviders\ApiProvider::getInstance()->getApi()->getOrderStatus($serviceSSL->remoteid);
        
        if($orderStatus['status'] !== 'active') {
            throw new Exception('Can not resend certificate. Order status is different than active.');
        }
        
        if(empty($orderStatus['ca_code'])) {
            throw new Exception('An error occurred. Certificate body is empty.');
        }
        $apiConf = (new \MGModule\RealtimeRegisterSsl\models\apiConfiguration\Repository())->get();
        $sendCertyficateTermplate = $apiConf->send_certificate_template;  
        if($sendCertyficateTermplate == NULL)
        {            
            sendMessage(\MGModule\RealtimeRegisterSsl\eServices\EmailTemplateService::SEND_CERTIFICATE_TEMPLATE_ID, $this->p['serviceid'], [
                'ssl_certyficate' => nl2br($orderStatus['ca_code']),
                'crt_code' => nl2br($orderStatus['crt_code']),
            ]);
        } 
        else
        {
            $templateName = \MGModule\RealtimeRegisterSsl\eServices\EmailTemplateService::getTemplateName($sendCertyficateTermplate);
            sendMessage($templateName, $this->p['serviceid'], [
                'ssl_certyficate' => nl2br($orderStatus['ca_code']),
                'crt_code' => nl2br($orderStatus['crt_code']),
            ]);
        }       
    }
}
