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

            $sslOrder->setValidFrom($order->startDate);
            $sslOrder->setValidTill($order->expiryDate);
            $sslOrder->setCa($caBundle);

            $sslOrder->setSubscriptionStarts($order->startDate);
            $sslOrder->setSubscriptionEnds($order->expiryDate);

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
        $sslOrder= $this->sslService;
        $sslOrder->configdata = array_merge((array) json_decode($currentOrder->data), (array) $sslOrder->configdata);
        if (!empty($this->orderdata)) {
            $sslOrder->setSSLStatus($this->orderdata['status']);
            $sslOrder->setDcvMethod(self::getDcvMethod($this->orderdata));
        }

        $sslOrder->save();
        return $this->orderdata;
    }

    private static function getDcvMethod($orderData) {
        $dcv = $orderData['command']['dcv'];;
        return $dcv[0]['type'] == 'http'
            ?'FILE'
            : $dcv[0]['type'];
    }
}
