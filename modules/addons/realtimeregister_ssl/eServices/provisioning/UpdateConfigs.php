<?php

namespace AddonModule\RealtimeRegisterSsl\eServices\provisioning;

use AddonModule\RealtimeRegisterSsl\eModels\whmcs\service\SSL;
use AddonModule\RealtimeRegisterSsl\eProviders\ApiProvider;
use AddonModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\KeyToIdMapping;
use AddonModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\Products;
use AddonModule\RealtimeRegisterSsl\eRepository\whmcs\service\SSL as SSLRepo;
use Exception;
use RealtimeRegister\Api\CertificatesApi;
use RealtimeRegister\Domain\Certificate;
use RealtimeRegister\Domain\Enum\DownloadFormatEnum;

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
            $this->updateConfigData();
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
        $sslOrder = (new SSLRepo())->getByRemoteId($cid);
        if ($sslOrder->status === SSL::CONFIGURATION_SUBMITTED) {
            $sslOrder->status = SSL::PENDING_INSTALLATION;
        }

        $sslOrder->setCa(base64_decode(
            $certificatesApi->downloadCertificate($order->id, DownloadFormatEnum::CA_BUNDLE_FORMAT)
        ));
        $sslOrder->setCrt($order->certificate);
        $sslOrder->setPartnerOrderId($order->id);

        $sslOrder->setValidFrom($order->startDate);
        $sslOrder->setValidTill($order->expiryDate);

        if ($order->subscriptionEndDate) {
            $sslOrder->setSubscriptionStarts($order->startDate);
            $sslOrder->setSubscriptionEnds($order->subscriptionEndDate);
        }

        if (isset($order->san)) {
            $sslOrder->setSanDetails(array_map(function ($sanEntry) {return ["san_name" => $sanEntry];}, $order->san));
        }

        $sslOrder->setDomain($order->domainName);

        $sslOrder->setProductId($order->product);
        $sslOrder->setSSLStatus($order->status);
        $sslOrder->setProductBrand($apiProduct->brand);
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
            if (!$cid) {
                continue;
            }
            $res = $api->listCertificates(null, null, null,['process:eq' => $cid]);
            foreach($res as $result) {
                $this->writeNewConfigdata($apiRepo, $result, $cid);
            }
        }
    }
}
