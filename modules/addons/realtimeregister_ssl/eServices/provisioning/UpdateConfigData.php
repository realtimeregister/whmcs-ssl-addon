<?php

namespace MGModule\RealtimeRegisterSsl\eServices\provisioning;

use MGModule\RealtimeRegisterSsl\eModels\whmcs\service\SSL;
use MGModule\RealtimeRegisterSsl\eProviders\ApiProvider;
use MGModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\KeyToIdMapping;
use MGModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\Products;
use SandwaveIo\RealtimeRegister\Api\CertificatesApi;
use SandwaveIo\RealtimeRegister\Api\ProcessesApi;
use SandwaveIo\RealtimeRegister\Domain\Certificate;
use WHMCS\Database\Capsule;

class UpdateConfigData
{
    private $sslService;
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
        return 'success';
    }
    
    public function updateConfigData()
    {
        if (!isset($this->sslService->remoteid) || empty($this->sslService->remoteid)) {
            return;
        }

        if (empty($this->orderdata)) {
            /** @var ProcessesApi $processesApi */
            $processesApi = ApiProvider::getInstance()->getApi(ProcessesApi::class);
            $process = $processesApi->info($this->sslService->remoteid);

            /** @var CertificatesApi $certificatesApi */
            $certificatesApi = ApiProvider::getInstance()->getApi(CertificatesApi::class);
            $certificateResults = $certificatesApi->listCertificates(null, null, null,['process:eq' => $process->id]);

            if ($certificateResults->count() === 1) {
                /** @var Certificate $order */
                $order = $certificateResults[0];
            }
        } else {
            $order = $this->orderdata;
        }
           
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
                    'pid',
                    $id
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

//        if (($order['status'] != 'expired') && ($order['status'] != 'cancelled'))
//        {
            /** @var SSL $sslOrder */
            $sslOrder = $this->sslService;

//            $sslOrder->setCa($order['ca_code']);
            $sslOrder->setCrt($order->certificate);
//            $sslOrder->setPartnerOrderId($order['partner_order_id']);

            $sslOrder->setValidFrom($order->startDate);
            $sslOrder->setValidTill($order->expiryDate);

            $sslOrder->setSubscriptionStarts($order->startDate);
            $sslOrder->setSubscriptionEnds($order->expiryDate);

            $sslOrder->setDomain($order->domainName);
            $sslOrder->setSSLStatus($order->status);
//            $sslOrder->setOrderStatusDescription($order['status_description']);

//            $sslOrder->setApproverMethod($order['approver_method']);
//            $sslOrder->setDcvMethod($order['dcv_method']);
            $sslOrder->setProductId($order->product);
//            $sslOrder->setSSLTotalDomains($order['total_domains']);
            
            if (
                !isset($this->sslService->configdata->product_brand)
                || empty($this->sslService->configdata->product_brand)
            ) {
                $sslOrder->setProductBrand($brandName);
            }
            
            $sslOrder->setSanDetails($order->san);
//            $sslOrder->setConfigdataKey("approveremail", $order['approver_email']);

            $sslOrder->save();
       // }

        return $order;
    }
}
