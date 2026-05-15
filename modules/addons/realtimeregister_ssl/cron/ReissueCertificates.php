<?php

namespace AddonModule\RealtimeRegisterSsl\cron;

use AddonModule\RealtimeRegisterSsl\Addon;
use AddonModule\RealtimeRegisterSsl\eHelpers\Whmcs;
use AddonModule\RealtimeRegisterSsl\eModels\whmcs\service\SSL;
use AddonModule\RealtimeRegisterSsl\eProviders\ApiProvider;
use AddonModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\KeyToIdMapping;
use AddonModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\Products;
use AddonModule\RealtimeRegisterSsl\eRepository\whmcs\service\SSL as SSLRepo;
use AddonModule\RealtimeRegisterSsl\eServices\provisioning\ConfigOptions;
use AddonModule\RealtimeRegisterSsl\models\logs\Repository as LogsRepo;
use AddonModule\RealtimeRegisterSsl\eServices\provisioning\GenerateCSR;
use AddonModule\RealtimeRegisterSsl\eServices\provisioning\SSLUtils;
use AddonModule\RealtimeRegisterSsl\eServices\provisioning\UpdateConfigData;
use AddonModule\RealtimeRegisterSsl\models\apiConfiguration\Repository as ApiConfiguration;
use DateTime;
use Exception;
use Illuminate\Database\Capsule\Manager as Capsule;
use RealtimeRegister\Api\CertificatesApi;

class ReissueCertificates extends BaseTask
{

    use SSLUtils;

    protected $defaultFrequency = 1440;
    protected $skipDailyCron = false;
    protected $defaultPriority = 4300;
    protected $defaultDescription = 'Automatically reissues certificates X days before expiry based on configuration.';
    protected $defaultName = 'Reissue Certificate';
    protected $outputs = ["reissued" => ["defaultValue" => 0, "identifier" => "reissued", "name" => "Certificates Reissued"]];
    protected $icon = "fas fa-sync";
    protected $successCountIdentifier = "reissued";
    protected $successKeyword = "Certificates Reissued";

    public function __invoke()
    {
        $logsRepo = new LogsRepo();
        $apiConf = (new ApiConfiguration())->get();

        if (!$apiConf->cron_reissue_certificate) {
            return;
        }

        $reissueDays = (int)$apiConf->reissue_days_before_expiry;

        if ($reissueDays <= 0) {
            return;
        }

        logActivity("Realtime Register SSL: Automatic Reissue started");
        Addon::I();

        $this->sslRepo = new SSLRepo();
        $sslOrders = $this->getSSLOrders([SSL::PENDING_INSTALLATION, SSL::FAILED_INSTALLATION, SSL::ACTIVE]);
        $reissuedCount = 0;

        foreach ($sslOrders as $sslOrder) {
            try {
                $configData = $sslOrder->configdata;
                
                $expiryDate = null;
                $subscriptionEndDate = null;
                if (!empty($configData->end_date?->date)) {
                    $subscriptionEndDate = new DateTime($configData->end_date->date);
                }

                if (!empty($configData->valid_till?->date)) {
                    $expiryDate = new DateTime($configData->valid_till->date);
                }

                if (!$subscriptionEndDate || !$expiryDate || $expiryDate >= $subscriptionEndDate) {
                    continue;
                }

                $daysLeft = $this->checkOrderExpiryDate($expiryDate);

                if ($daysLeft <= $reissueDays) {
                    $this->reissue($sslOrder, $apiConf);
                    $reissuedCount++;
                }
            } catch (Exception $ex) {
                $logsRepo->addLog($sslOrder->userid, $sslOrder->serviceid, 'error', "Error when reissuing certificate:" . $ex->getMessage());
            }
        }

        logActivity('Automatic Reissue completed. Number of certificates reissued: ' . $reissuedCount, 0);
        $this->output("reissued", $reissuedCount);
    }

    private function checkOrderExpiryDate(DateTime $expiryDate): int
    {
        $now = new DateTime();
        $now->setTime(0, 0);
        $expiryDate->setTime(0, 0);

        $diff = $now->diff($expiryDate);
        return (int)$diff->format("%r%a");
    }

    /**
     * @throws Exception
     */
    private function reissue(SSL $sslOrder, $apiConf) : void
    {
        $serviceId = $sslOrder->serviceid;
        $configData = $sslOrder->configdata;
        $certificateId = $sslOrder->getCertificateId();
        $csr = $configData->csr;
        $privateKey = null;
        $commonName = $configData->domain;
        $logs = new LogsRepo();

        if ($apiConf->reissue_generate_new_csr) {
            $post = [
                'commonName' => $configData->domain,
                'countryName' => $configData->country,
                'stateOrProvinceName' => $configData->state,
                'localityName' => $configData->city,
                'organizationName' => $configData->orgname,
                'emailAddress' => $configData->email,
                'serviceID' => $serviceId,
                'doNotSaveToDatabase' => true
            ];
            $params = ['serviceid' => $serviceId];
            
            $generateCSR = new GenerateCSR($params, $post);
            $result = json_decode($generateCSR->run(), true);
            
            if ($result['success']) {
                $csr = $result['public_key'];
                $privateKey = $result['private_key']; // Already encrypted
            } else {
                throw new Exception("CSR Generation failed: " . $result['msg']);
            }
        }

        if (!$certificateId) {
            throw new Exception("Certificate ID missing for SSL Order.");
        }

        $dcv = [];
        // Main domain DCV
        $dcv[] = [
            'commonName' => $commonName,
            'type' => $this->mapDcvType($configData->dcv_method ?? $configData->approvalmethod),
            'email' => $this->getApproverEmail($sslOrder, $commonName)
        ];

        // SANs DCV
        $sans = [];
        $allSans = $sslOrder->getSanDetails() ?? [];

        foreach ($allSans as $san) {
            $sans[] = $san->san_name;
            $dcv[] = [
                'commonName' => $san->san_name,
                'type' => $this->mapDcvType($san->method),
                'email' => $san->email ?? $this->getApproverEmail($sslOrder, $commonName)
            ];
        }
        $service = Capsule::table('tblhosting')->where('id', $serviceId)->first();
        $product = Capsule::table('tblproducts')->where('id', $service->packageid)->first();
        $authKey = $product->{ConfigOptions::AUTH_KEY_ENABLED} === 'on';

        $productDetails = ApiProvider::getInstance()
            ->getApi(CertificatesApi::class)
            ->getProduct($sslOrder->getProductId());

        if ($authKey) {
            $apiRepo = new Products();
            $apiProduct = $apiRepo->getProduct(KeyToIdMapping::getIdByKey($product->{ConfigOptions::API_PRODUCT_ID}));
            $authKey = $this->processAuthKeyValidation(
                $commonName,
                $apiProduct->product,
                $csr,
                $dcv,
                $sslOrder->userid,
                $serviceId
            );
        }

        $reissueData = [
            'csr' => $csr,
            'san' => $sans,
            'language' => null,
            'dcv' => $dcv,
            'domainName' => $configData->commonName,
        ];

        $reissueData = array_merge($this->mapRequestFields((array) $configData, $productDetails), $reissueData);

        $reissueData = $this->reissueCertificate($certificateId,
            $reissueData,
            $commonName,
            $authKey,
            $sslOrder->userid,
            $serviceId
        );

        $sslOrder->setRemoteId($reissueData->processId);
        $sslOrder->status = SSL::CONFIGURATION_SUBMITTED;
        $sslOrder->setConfigdataKey('csr', $csr);
        if ($privateKey) {
            $sslOrder->setPrivateKey($privateKey);
        }
        $sslOrder->save();

        $configDataUpdate = new UpdateConfigData($sslOrder);
        $configDataUpdate->run();

        $logs->addLog($sslOrder->userid,
             $sslOrder->serviceid,
            'success',
            'The automatic reissue order has been placed'
            . ($reissueData->certificateId ? ', the certificate was issued immediately.' : '.')
        );

        if ($reissueData->certificateId) {
            $this->autoInstallCertificate($sslOrder);
        }
    }

    private function getApproverEmail(SSL $sslOrder, string $domainName) : ?string
    {
        return array_filter($sslOrder->getApproverEmails() ?? [], function($email) use ($domainName) {
            return str_ends_with($email, '@' . $domainName);
        })[0]
            ?? $sslOrder->getApproverEmail()
            ?? null;
    }
}
