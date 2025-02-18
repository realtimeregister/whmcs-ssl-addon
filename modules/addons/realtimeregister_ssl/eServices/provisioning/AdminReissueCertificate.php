<?php

namespace AddonModule\RealtimeRegisterSsl\eServices\provisioning;

use AddonModule\RealtimeRegisterSsl\addonLibs\Lang;
use AddonModule\RealtimeRegisterSsl\eHelpers\Domains;
use AddonModule\RealtimeRegisterSsl\eHelpers\SansDomains;
use AddonModule\RealtimeRegisterSsl\eModels\whmcs\service\SSL;
use AddonModule\RealtimeRegisterSsl\eProviders\ApiProvider;
use AddonModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\KeyToIdMapping;
use AddonModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\Products;
use AddonModule\RealtimeRegisterSsl\eRepository\whmcs\service\SSL as SSLRepo;
use AddonModule\RealtimeRegisterSsl\models\logs\Repository as LogsRepo;
use Exception;
use RealtimeRegister\Api\CertificatesApi;
use RealtimeRegister\Domain\CertificateInfoProcess;
use function ModuleBuildParams;

// TODO remove duplicate code also present in SSLStepTwo.php
class AdminReissueCertificate extends Ajax
{
    use SSLUtils;
    private $p;
    private $serviceParams;

    function __construct(&$params)
    {
        $this->p = &$params;
    }

    public function run(): void
    {
        try {
            $this->reissueController();
        } catch (Exception $ex) {
            $this->response(false, $ex->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    private function reissueController()
    {
        if ($this->p['action'] === 'reissueCertificate') {
            $this->reissueCertificate();
        }

        if ($this->p['action'] === 'getApprovals') {
            $this->getApprovals();
        }
    }

    private function reissueCertificate()
    {
        $sslRepo = new SSLRepo();
        $sslService = $sslRepo->getByServiceId($this->p['serviceId']);
        $decodeCSR = ApiProvider::getInstance()->getApi(CertificatesApi::class)->decodeCsr($this->p['csr']);
        $mainDomain = $decodeCSR['commonName'];


        if (is_null($sslService)) {
            throw new Exception('Create has not been initialized.');
        }

        if ($this->p['userID'] != $sslService->userid) {
            throw new Exception('An error occurred.');
        }

        $data = [
            'csr' => $this->p['csr'],
            'approver_email' => $this->p['approveremail'],
        ];

        $sanDomains =  SansDomains::parseDomains($this->p['sanDomains']);
        $wildcardDomains =  SansDomains::parseDomains($this->p['sanDomainsWildcard']);
        $allSans = array_merge($sanDomains, $wildcardDomains);

        $productDetails = ApiProvider::getInstance()
            ->getApi(CertificatesApi::class)
            ->getProduct($sslService->getProductId());
        $orderDetails = $sslService->getConfigData();

        $orderFields = $this->mapRequestFields($orderDetails, $productDetails);

        $dcv = array_map(fn($dcvEntry) => [...$dcvEntry,
            'type' => $dcvEntry['type'] === 'HTTP' ? 'FILE' : $dcvEntry['type']],
            $this->p['dcv']);
        /**
         * @var $responseData CertificateInfoProcess
         */
         $responseData = ApiProvider::getInstance()
             ->getApi(CertificatesApi::class)
             ->reissueCertificate(
                 $sslService->getCertificateId(),
                 $this->p['csr'],
                 $allSans,
                 $orderFields['organization'],
                 null,
                 $orderFields['address'],
                 $orderFields['postalCode'],
                 $orderFields['city'],
                 $orderFields['coc'],
                 $orderFields['approver'],
                 null,
                 null,
                 empty($dcv) ? null : $dcv,
                 $mainDomain,
                 null,
                 $orderFields['state']
             );

        $logs = new LogsRepo();

        $this->processDcvEntries($responseData->validations?->dcv->toArray() ?? []);

        $sslService->setRemoteId($responseData->processId);
        $sslService->setConfigdataKey('private_key', null);
        $sslService->setConfigdataKey('csr', $this->p['csr']);
        $sslService->setCrt('--placeholder--');
        $sslService->setCa(null);
        $sslService->status = SSL::CONFIGURATION_SUBMITTED;
        $sslService->save();

        try {
            $configDataUpdate = new UpdateConfigData($sslService);
            $configDataUpdate->run();
        } catch (Exception $e) {
            $logs->addLog(
                $this->p['userid'],
                $this->p['serviceid'],
                'error',
                '[' . $mainDomain . '] Error:' . $e->getMessage()
            );
            $sslService->setSSLStatus('SUSPENDED');
            $sslService->save();
        }

        $this->response(true, 'Reissue was successfully requested.');
    }

    private function moduleBuildParams()
    {
        $this->serviceParams = ModuleBuildParams($this->p['serviceId']);
        if (empty($this->serviceParams)) {
            throw new Exception('Can not build module params.');
        }
    }

    private function getApprovals()
    {
        $mainDomain = ApiProvider::getInstance()
            ->getApi(CertificatesApi::class)
            ->decodeCsr($this->p['csr'])['commonName'];

        $this->validateSanDomains($mainDomain);
        $this->validateSansDomainsWildcard();

        $domains = $mainDomain . PHP_EOL . $this->p['sanDomains'] . PHP_EOL . $this->p['sanDomainsWildcard'];
        $parseDomains = SansDomains::parseDomains($domains);
        $SSLStepTwoJS = new SSLStepTwoJS($this->p);
        $this->response(true, 'Approve Emails', $SSLStepTwoJS->fetchApprovalEmailsForSansDomains($parseDomains));
    }

    /**
     * @throws Exception
     */
    private function validateSanDomains(string $commonName): void
    {
        $this->moduleBuildParams();
        $sansDomains = SansDomains::parseDomains($this->p['sanDomains']);

        $apiProductId = $this->serviceParams[ConfigOptions::API_PRODUCT_ID];

        $invalidDomains = Domains::getInvalidDomains($sansDomains);

        if (count($invalidDomains)) {
            throw new Exception(Lang::getInstance()->T('incorrectSans') . implode(', ', $invalidDomains));
        }

        $productBrandRepository = Products::getInstance();
        $productBrand = $productBrandRepository->getProduct(KeyToIdMapping::getIdByKey($apiProductId));
        $sanCount = $this->getSanDomainCount($sansDomains, $commonName, $productBrand);

        $sansLimit = $this->getSansLimit($this->serviceParams);
        if ($sanCount > $sansLimit) {
            throw new Exception(Lang::getInstance()->T('exceededLimitOfSans'));
        }
    }

    /**
     * @throws Exception
     */
    private function validateSansDomainsWildcard()
    {
        $sansDomainsWildcard = $this->p['sanDomainsWildcard'];
        $sansDomainsWildcard = SansDomains::parseDomains($sansDomainsWildcard);

        foreach ($sansDomainsWildcard as $domain) {
            $check = substr($domain, 0, 2);
            if ($check != '*.') {
                throw new Exception('SAN\'s Wildcard are incorrect');
            }
            $domaincheck = Domains::validateDomain(substr($domain, 2));
            if ($domaincheck !== true) {
                throw new Exception('SAN\'s Wildcard are incorrect');
            }
        }

        $sansWildcardLimit = $this->getSansLimitWildcard($this->serviceParams);
        if (count($sansDomainsWildcard) > $sansWildcardLimit) {
            throw new Exception(Lang::getInstance()->T('exceededLimitOfSans'));
        }
    }
}
