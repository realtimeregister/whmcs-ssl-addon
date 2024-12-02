<?php

namespace AddonModule\RealtimeRegisterSsl\eServices\provisioning;

use AddonModule\RealtimeRegisterSsl\eModels\whmcs\service\SSL;
use AddonModule\RealtimeRegisterSsl\eProviders\ApiProvider;
use AddonModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\KeyToIdMapping;
use AddonModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\Products;
use AddonModule\RealtimeRegisterSsl\models\orders\Repository as OrderRepo;
use RealtimeRegister\Api\CertificatesApi;
use RealtimeRegister\Api\ProcessesApi;
use RealtimeRegister\Domain\Enum\DownloadFormatEnum;
use WHMCS\Database\Capsule;

class UpdateConfigData
{
    private SSL $sslService;
    private $orderdata;
    
    public function __construct(SSL $sslService, $orderdata = [])
    {
        $this->sslService = $sslService;
        if (empty($orderdata)) {
            $processesApi = ApiProvider::getInstance()
                ->getApi(ProcessesApi::class);
            $process = $processesApi->get($sslService->getRemoteId());
            $infoProcess = $processesApi->info($process->id)
                ->toArray();

            $this->orderdata = [
                'status' => $process->status,
                'dcv' => $infoProcess['validations']['dcv']
            ];
        } else {
            $this->orderdata = $orderdata;
        }
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

        $orderRepo = new OrderRepo();

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
            $caBundle = base64_decode(
                $certificatesApi->downloadCertificate($order->id, DownloadFormatEnum::CA_BUNDLE_FORMAT)
            );
            $apiRepo = new Products();

            if (
                !isset($this->sslService->configdata->product_brand) || empty($this->sslService->configdata->product_brand)
            ) {
                $checkTable = Capsule::schema()->hasTable(Products::REALTIMEREGISTERSSL_PRODUCT_BRAND);

                $brandName = null;
                if ($checkTable !== false) {
                    $id = KeyToIdMapping::getIdByKey($order->product);
                    $productData = Capsule::table(Products::REALTIMEREGISTERSSL_PRODUCT_BRAND)->where([
                            'pid' => $id
                        ]
                    )->first();
                    if (!empty($productData->brand)) {
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
            $sslOrder->setSSLStatus($order->status);
            $sslOrder->setPartnerOrderId($order->providerId);

            $sslOrder->status = SSL::PENDING_INSTALLATION;
            $sslOrder->setCertificateId($order->id);

            $sslOrder->setValidFrom($order->startDate);
            $sslOrder->setValidTill($order->expiryDate);
            $sslOrder->setCa($caBundle);

            if ($order->subscriptionEndDate) {
                $sslOrder->setSubscriptionStarts($order->startDate);
                $sslOrder->setSubscriptionEnds($order->subscriptionEndDate);
            }
            
            $sslOrder->setDomain($order->domainName);

            $sslOrder->setProductId($order->product);

            if (
                !isset($this->sslService->configdata->product_brand)
                || empty($this->sslService->configdata->product_brand)
            ) {
                $sslOrder->setProductBrand($brandName);
            }

            if (isset($order->san)) {
                $sslOrder->setSanDetails(array_map(function ($sanEntry) {return ["san_name" => $sanEntry];}, $order->san));
            }

            $sslOrder->save();
            return $order;
        }

        $currentOrder = $orderRepo->getByServiceId($this->sslService->serviceid);
        $sslOrder = $this->sslService;
        $sslOrder->configdata = array_merge((array) json_decode($currentOrder->data), (array) $sslOrder->configdata);

        if (isset($this->orderdata['status'])) {
            $sslOrder->setSSLStatus($this->orderdata['status']);
            $sslOrder->setOrderStatusDescription($this->orderdata['status']);
        }
        if (isset($this->orderdata['dcv'])) {
            $this->handleDcvMethod();
        }

        $sslOrder->save();
        return $this->orderdata;
    }

    private function handleDcvMethod() : void {
        $sslOrder = $this->sslService;
        $dcv = array_values(
            array_filter($this->orderdata['dcv'],
            function ($dcv) {return $dcv['commonName'] == $this->sslService->getDomain();}
            ))[0];
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
                        'dns' => [
                            'record' => $dcv['dnsRecord'] . ' ' . $dcv['dnsType']  . ' ' . $dcv['dnsContents']
                        ]];
                    break;
                default:
                    $sanEntry['validation_method'] = 'email';
                    break;
            }
            $san_details[] = $sanEntry;
        }
        $sslOrder->setSanDetails($san_details);
    }
}
