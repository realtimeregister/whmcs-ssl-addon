<?php

namespace AddonModule\RealtimeRegisterSsl\eHelpers;

use AddonModule\RealtimeRegisterSsl\eModels\whmcs\service\SSL;
use AddonModule\RealtimeRegisterSsl\eProviders\ApiProvider;
use AddonModule\RealtimeRegisterSsl\models\whmcs\service\Repository;
use DateTime;
use RealtimeRegister\Api\ProcessesApi;

class SSLSummary
{
    private $clientID = null;
    private $services = [];
    private $apiOrders = null;
    private $sslRepo = null;

    public function __construct($clientID)
    {
        $this->clientID = $clientID;
        $this->sslRepo = new \AddonModule\RealtimeRegisterSsl\eRepository\whmcs\service\SSL();
        $this->loadClientsSSLServices();
    }


    public function getTotalSSLOrdersCount()
    {
        return count($this->services);
    }

    public function getTotalSSLOrders()
    {
        return $this->services;
    }

    public function getUnpaidSSLOrdersCount()
    {
        return count($this->getUnpaidSSLOrders());
    }

    public function getUnpaidSSLOrders()
    {
        $services = [];
        foreach ($this->services as $service) {
            $invoiceID = $service->order()->invoiceid;
            try {
                $invoice = new \AddonModule\RealtimeRegisterSsl\models\whmcs\invoices\Invoice($invoiceID);
            } catch (\Exception $ex) {
                continue;
            }

            if ($invoice->getStatus() == 'Unpaid') {
                $services[] = $service;
            }
        }

        return $services;
    }

    public function getProcessingSSLOrdersCount()
    {
        return count($this->getProcessingSSLOrders());
    }

    public function getProcessingSSLOrders()
    {
        $services = [];

        foreach ($this->services as $service) {
            if ($this->getSSLCertificateStatus($service->id) == 'processing') {
                $services[] = $service;
            }
        }

        return $services;
    }

    public function getExpiresSoonSSLOrdersCount()
    {
        return count($this->getExpiresSoonSSLOrders());
    }

    public function getExpiresSoonSSLOrders()
    {
        $services = [];

        $daysBefore = 30;
        $apiConf = (new \AddonModule\RealtimeRegisterSsl\models\apiConfiguration\Repository())->get();
        $expiresSoonSelectedDays = $apiConf->summary_expires_soon_days;
        if ($expiresSoonSelectedDays != null && trim($expiresSoonSelectedDays) != '') {
            $daysBefore = $expiresSoonSelectedDays;
        }

        foreach ($this->services as $service) {
            $SSLOrder = new SSL();

            $ssl = $SSLOrder->getWhere(['serviceid' => $service->id, 'userid' => $service->clientID])->first();

            if ($ssl == null || $ssl->remoteid == '') {
                continue;
            }
            $expiryDate = $this->getSSLCertificateValidTillDate($service->id)?->date;
            $sslStatus = $this->getSSLCertificateStatus($service->id);

            if ($expiryDate != null
                && $expiryDate != '0000-00-00'
                && ($sslStatus === 'ACTIVE' || $sslStatus === 'COMPLETED')
                && $this->checkOrderExpiryDate($expiryDate, $daysBefore)) {
                $services[] = $service;
            }
        }

        return $services;
    }

    private function checkOrderExpiryDate($expiryDate, $days = 30)
    {
        $expiry = new DateTime($expiryDate);
        $today = new DateTime();

        if ($expiry < $today) {
            // Date is in the past
            return false;
        }

        $diff = $expiry->diff($today)->format("%a");

        return $diff <= $days;
    }

    private function getSSLCertificateValidTillDate($serviceID)
    {
        $sslService = $this->sslRepo->getByServiceId((int)$serviceID);
        return $sslService->getConfigdataKey('valid_till');
    }

    private function getSSLCertificateStatus($serviceID)
    {
        $sslService = $this->sslRepo->getByServiceId((int)$serviceID);
        if ($sslService == null)
            return;

        return $sslService->getConfigdataKey('ssl_status');
    }

    private function loadClientsSSLServices()
    {
        $services = new Repository();
        $services->onlyClient($this->clientID)->onlyStatus(['Active', 'Suspended', 'Pending']);

        $this->services = [];
        foreach ($services->get() as $service) {
            $product = $service->product();

            //check if product is Realtime Register Ssl
            if ($product->serverType == 'realtimeregister_ssl') {
                $this->services[] = $service;
            }
        }
    }

    private function loadSSLOrdersFromAPI()
    {
        $this->apiOrders = [];
        /** @var ProcessesApi $processesApi */
        $processesApi = ApiProvider::getInstance()->getApi(ProcessesApi::class);
        foreach ($this->services as $service) {
            $SSLOrder = new SSL();

            $ssl = $SSLOrder->getWhere(['serviceid' => $service->id, 'userid' => $service->clientID])->first();

            if ($ssl == null || $ssl->remoteid == '') {
                continue;
            }

            //get order details from API
            $this->apiOrders[] = $processesApi->get($ssl->remoteid);
        }
    }
}
