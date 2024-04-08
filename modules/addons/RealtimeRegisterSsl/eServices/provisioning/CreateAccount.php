<?php

namespace MGModule\RealtimeRegisterSsl\eServices\provisioning;

use Exception;

class CreateAccount {

    private $p;

    function __construct(&$params) {
        $this->p = &$params;

    }

    public function run() {
        try {
            $this->CreateAccount();
        } catch (Exception $e) {
            return $e->getMessage();
        }
        return 'success';

    }

    public function CreateAccount() {
        $repo       = new \MGModule\RealtimeRegisterSsl\eRepository\whmcs\service\SSL();
        $serviceSSL = $repo->getByServiceId($this->p['serviceid']);

        if (!is_null($serviceSSL)) {
            throw new Exception('Already created');
        }

        $sslModel                 = new \MGModule\RealtimeRegisterSsl\eModels\whmcs\service\SSL();
        $sslModel->userid         = $this->p['clientsdetails']['userid'];
        $sslModel->serviceid      = $this->p['serviceid'];
        $sslModel->remoteid       = '';
        $sslModel->module         = 'RealtimeRegisterSsl';
        $sslModel->certtype       = '';
        $sslModel->completiondate = '';
        $sslModel->status         = 'Awaiting Configuration';        
        $sslModel->save();

        sendMessage(\MGModule\RealtimeRegisterSsl\eServices\EmailTemplateService::CONFIGURATION_TEMPLATE_ID, $this->p['serviceid'], [
            'ssl_configuration_link' => \MGModule\RealtimeRegisterSsl\eRepository\whmcs\config\Config::getInstance()->getConfigureSSLLink($sslModel->id, $sslModel->serviceid ),
            'ssl_configuration_url'  => \MGModule\RealtimeRegisterSsl\eRepository\whmcs\config\Config::getInstance()->getConfigureSSLUrl($sslModel->id, $sslModel->serviceid ),
        ]);

    }

}
