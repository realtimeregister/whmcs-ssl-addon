<?php

namespace MGModule\RealtimeRegisterSsl\eServices\provisioning;

use MGModule\RealtimeRegisterSsl\eModels\whmcs\service\SSL;
use MGModule\RealtimeRegisterSsl\eProviders\ApiProvider;
use MGModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\KeyToIdMapping;
use MGModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\Products;
use MGModule\RealtimeRegisterSsl\models\orders\Order;
use MGModule\RealtimeRegisterSsl\models\orders\Repository as OrderRepo;
use SandwaveIo\RealtimeRegister\Api\CertificatesApi;
use SandwaveIo\RealtimeRegister\Domain\Certificate;
use WHMCS\Database\Capsule;
use function GuzzleHttp\is_host_in_noproxy;

class UpdateConfigData
{
    private SSL $sslService;
    private $orderdata;
    
    public function __construct($sslService, $orderdata = [])
    {
        $this->sslService = $sslService;
        $this->orderdata = $orderdata;
    }
    
    public function run()
    {
        try {
            return $this->updateConfigData();
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }
    
    public function updateConfigData()
    {
        if (!isset($this->sslService->remoteid) || empty($this->sslService->remoteid)) {
            return null;
        }

        $caBundle = null;

        $certificatesApi = ApiProvider::getInstance()->getApi(CertificatesApi::class);
        $certificateResults = $certificatesApi->listCertificates(
            null,
            null,
            null,
            ['process:eq' => $this->sslService->remoteid]
        );

        if ($certificateResults->count() === 1) {
            /** @var CertificatesApi $certificatesApi */
            $order = $certificateResults[0];
            $caBundle = base64_decode($certificatesApi->downloadCertificate($order->id, 'CA_BUNDLE'));
            $apiRepo = new Products();

            if (
                !isset($this->sslService->configdata->product_brand) || empty($this->sslService->configdata->product_brand)
            ) {
                $checkTable = Capsule::schema()->hasTable(Products::MGFW_REALTIMEREGISTERSSL_PRODUCT_BRAND);

                $brandName = null;
                if ($checkTable !== false) {
                    if (is_object($order)) {
                        $id = KeyToIdMapping::getIdByKey($order->product);
                    } else {
                        $id = KeyToIdMapping::getIdByKey($order['command']['product']);
                    }
                    $productData = Capsule::table(Products::MGFW_REALTIMEREGISTERSSL_PRODUCT_BRAND)->where([
                            'pid' => $id
                        ]
                    )->first();
                    if (isset($productData->brand) && !empty($productData->brand)) {
                        $brandName = $productData->brand;
                    }
                }

                if ($brandName === null) {
                    $apiProduct = $apiRepo->getProduct($order->product);
                    $apiProduct->brand = $brandName;
                }
            }

            /** @var SSL $sslOrder */
            $sslOrder = $this->sslService;

            $sslOrder->setCrt($order->certificate);
            $sslOrder->setSSLStatus('COMPLETED');

            $sslOrder->setValidFrom($order->startDate);
            $sslOrder->setValidTill($order->expiryDate);
            $sslOrder->setCa($caBundle);

            //$sslOrder->setSubscriptionStarts($order->startDate);
            //$sslOrder->setSubscriptionEnds($order->expiryDate);

            $sslOrder->setDomain($order->domainName);

            $sslOrder->setProductId($order->product);

            if (
                !isset($this->sslService->configdata->product_brand)
                || empty($this->sslService->configdata->product_brand)
            ) {
                $sslOrder->setProductBrand($brandName);
            }

            $sslOrder->setSanDetails($order->san);
            $sslOrder->save();
            return $order;
        }

        $orderRepo = new OrderRepo();
        $currentOrder = $orderRepo->getByServiceId($this->sslService->serviceid);
        $sslOrder = $this->sslService;
        $sslOrder->configdata = array_merge((array) json_decode($currentOrder->data), (array) $sslOrder->configdata);
        if (!empty($this->orderdata)) {
            if (isset($this->orderdata['status'])) {
                $sslOrder->setSSLStatus($this->orderdata['status']);
            }
            if (isset($this->orderdata['dcv'])) {
                $this->handleDcvMethod();
            }
        }

        $sslOrder->save();
        return $this->orderdata;
    }

    private function handleDcvMethod() : void {
        $sslOrder = $this->sslService;
        $dcv = array_filter($this->orderdata['dcv'],
            function ($dcv) {return $dcv['commonName'] == $this->sslService->getDomain();})[0];
        switch ($dcv['type']) {
            case 'FILE':
                $sslOrder->setDcvMethod('http');
                $sslOrder->setApproverMethod(
                    ['http' => [
                        'link' => $dcv['fileLocation'],
                        'content' => $dcv['fileContents']
                    ]]
                );
                break;
            case "DNS":
                $sslOrder->setDcvMethod('dns');
                $sslOrder->setApproverMethod(
                    ['dns' => [
                        'record' => $dcv['dnsRecord'] . ' ' . $dcv['dnsType']  . ' ' . $dcv['dnsContents']
                    ]]
                );
                break;
            default:
                $sslOrder->setDcvMethod('email');
                $sslOrder->setApproverEmail($dcv['email']);
        }
        $san = array_filter($this->orderdata['dcv'],
            function ($dcv) {return $dcv['commonName'] != $this->sslService->getDomain();});

        $san_details = [];
        foreach ($san as $dcv) {
            $sanEntry = [
                'san_name' => $dcv['commonName']
            ];
            switch ($dcv['type']) {
                case "FILE":
                    $sanEntry['validation_method'] = 'http';
                    $sanEntry['validation'] = [
                        'http' => [
                            'link' => $dcv['fileLocation'],
                            'content' => $dcv['fileContents']
                            ]];
                    break;
                case "DNS":
                    $sanEntry['validation_method'] = 'dns';
                    $sanEntry['validation'] = [
                        ['dns' => [
                            'record' => $dcv['dnsRecord'] . ' ' . $dcv['dnsType']  . ' ' . $dcv['dnsContents']
                        ]]];
                    break;
                default:
                    $sanEntry['validation_method'] = 'email';
                    $sanEntry['validation'] = [
                        ['dns' => [
                            'record' => $dcv['dnsRecord'] . ' ' . $dcv['dnsType']  . ' ' . $dcv['dnsContents']
                        ]]];
                    break;
            }
            $san_details[] = $sanEntry;
        }
        $sslOrder->setSanDetails($san_details);
    }
}
