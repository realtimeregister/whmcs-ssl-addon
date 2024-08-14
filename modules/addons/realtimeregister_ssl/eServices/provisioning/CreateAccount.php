<?php

namespace MGModule\RealtimeRegisterSsl\eServices\provisioning;

use Exception;
use MGModule\RealtimeRegisterSsl\eModels\whmcs\service\SSL;
use MGModule\RealtimeRegisterSsl\eRepository\whmcs\config\Config;
use MGModule\RealtimeRegisterSsl\eServices\EmailTemplateService;

class CreateAccount
{
    private $p;

    public function __construct(&$params)
    {
        $this->p = &$params;
    }

    public function run()
    {
        try {
            $this->CreateAccount();
        } catch (Exception $e) {
            return $e->getMessage();
        }
        return 'success';
    }

    public function CreateAccount()
    {
        $repo = new \MGModule\RealtimeRegisterSsl\eRepository\whmcs\service\SSL();
        $serviceSSL = $repo->getByServiceId($this->p['serviceid']);

        if (!is_null($serviceSSL)) {
            throw new Exception('Already created');
        }

        $sslModel = new SSL();
        $sslModel->userid = $this->p['clientsdetails']['userid'];
        $sslModel->serviceid = $this->p['serviceid'];
        $sslModel->remoteid = '';
        $sslModel->module = 'realtimeregister_ssl';
        $sslModel->certtype = '';
        $sslModel->completiondate = '';
        $sslModel->status = 'Awaiting Configuration';
        $sslModel->save();

        sendMessage(EmailTemplateService::CONFIGURATION_TEMPLATE_ID, $this->p['serviceid'], [
            'ssl_configuration_link' => Config::getInstance()->getConfigureSSLLink($sslModel->id, $sslModel->serviceid),
            'ssl_configuration_url' => Config::getInstance()->getConfigureSSLUrl($sslModel->id, $sslModel->serviceid),
        ]);
    }
}
