<?php

namespace AddonModule\RealtimeRegisterSsl\eServices\provisioning;

use Exception;
use AddonModule\RealtimeRegisterSsl\eModels\whmcs\service\SSL;
use AddonModule\RealtimeRegisterSsl\eProviders\ApiProvider;
use AddonModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\KeyToIdMapping;
use AddonModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\Products;
use SandwaveIo\RealtimeRegister\Api\CertificatesApi;
use SandwaveIo\RealtimeRegister\Domain\Certificate;
use SandwaveIo\RealtimeRegister\Domain\Enum\DownloadFormatEnum;

class UpdateConfigs
{
    private $sslService;
    private ?\AddonModule\RealtimeRegisterSsl\eRepository\whmcs\service\SSL $sslRepo;
    private bool $processingOnly;
    private array $cids;

    public function __construct($cids, $processingOnly)
    {
        $this->cids = $cids;
        $this->sslRepo = new \AddonModule\RealtimeRegisterSsl\eRepository\whmcs\service\SSL();
        $this->processingOnly = $processingOnly;
    }
    
    public function run()
    {
        try {
            return $this->updateConfigData();
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
        return 'success';
    }

    private function writeNewConfigdata(Products $apiRepo, Certificate $order, int $cid)
    {
        $apiProduct = $apiRepo->getProduct(KeyToIdMapping::getIdByKey($order->product));

        /** @var CertificatesApi $certificatesApi */
        $certificatesApi = ApiProvider::getInstance()->getApi(CertificatesApi::class);

        /** @var SSL $sslOrder */
        $sslOrder = SSL::whereRemoteId($cid)->first();

        $sslOrder->setCa($certificatesApi->downloadCertificate($order->id, DownloadFormatEnum::CA_FORMAT));
        $sslOrder->setCrt($order->certificate);
        $sslOrder->setPartnerOrderId($order->id);

        $sslOrder->setStatus($order->status);
        $sslOrder->setValidFrom(($order->startDate)->format('Y-m-d H:i:s'));
        $sslOrder->setValidTill(($order->expiryDate)->format('Y-m-d H:i:s'));

        $sslOrder->setDomain($order->domainName);

        $sslOrder->setProductId(KeyToIdMapping::getIdByKey($order->product));
        $sslOrder->setProductId($order->product);
        $sslOrder->setSSLStatus($order->status);
        $sslOrder->setProductBrand($apiProduct->brand);
        $sslOrder->setConfigdataKey('valid_from', ($order->startDate)->format('Y-m-d H:i:s'));
        $sslOrder->setConfigdataKey('valid_till', ($order->expiryDate)->format('Y-m-d H:i:s'));
        $sslOrder->save();

        logActivity($cid.':'.$order->product);
    }

    public function updateConfigData()
    {
        if (!isset($this->cids) || empty($this->cids)) {
            return;
        }

        /** @var CertificatesApi $api */
        $api = ApiProvider::getInstance()->getApi(CertificatesApi::class);

        /** @var Certificate[] $orders */
        $apiRepo = new Products();
        foreach ($this->cids as $cid) {
            $res = $api->listCertificates(null, null, null,['process:eq' => $cid]);
            foreach($res as $result) {
                $this->writeNewConfigdata($apiRepo, $result, $cid);
            }
        }
    }
}
