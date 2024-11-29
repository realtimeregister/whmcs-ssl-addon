<?php

namespace AddonModule\RealtimeRegisterSsl\eServices\provisioning;

use AddonModule\RealtimeRegisterSsl\addonLibs\Lang;
use AddonModule\RealtimeRegisterSsl\eHelpers\Domains;
use AddonModule\RealtimeRegisterSsl\eHelpers\SansDomains;
use AddonModule\RealtimeRegisterSsl\eProviders\ApiProvider;
use AddonModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\KeyToIdMapping;
use AddonModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\Products;
use AddonModule\RealtimeRegisterSsl\eRepository\whmcs\service\SSL;
use AddonModule\RealtimeRegisterSsl\eServices\ManagementPanel\Api\Panel\Panel;
use AddonModule\RealtimeRegisterSsl\eServices\ManagementPanel\Dns\DnsControl;
use AddonModule\RealtimeRegisterSsl\eServices\ManagementPanel\File\FileControl;
use AddonModule\RealtimeRegisterSsl\models\logs\Repository as LogsRepo;
use Exception;
use RealtimeRegister\Api\CertificatesApi;
use RealtimeRegister\Domain\CertificateInfoProcess;
use function ModuleBuildParams;

class AdminReissueCertificate extends Ajax
{
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
        $sslRepo = new SSL();
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
        $orderDetails = (array) $sslService->configdata;

        $mapping = [
            'organization' => 'orgname',
            'country' => 'country',
            'state' => 'state',
            'address' => 'address1',
            'postalCode' => 'postcode',
            'city' => 'city',
            'dcv' => 'dcv'
        ];

        $orderFields = [];
        foreach ($productDetails->requiredFields as $value) {
            if ($value === 'approver') {
                $orderFields['approver'] = [
                    'firstName' => $orderDetails['firstname'],
                    'lastName' => $orderDetails['lastname'],
                    'jobTitle' => $orderDetails['jobtitle'],
                    'email' => $orderDetails['email'],
                    'voice' => $orderDetails['phonenumber']
                ];
            } else {
                $orderFields[$value] = $sslService[$mapping[$value]];
            }
        }

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
                 null,
                 $orderFields['approver'],
                 $orderFields['country'],
                 null,
                 empty($dcv) ? null : $dcv,
                 $mainDomain,
                 null,
                 $orderFields['state']
             );

        $logs = new LogsRepo();

        foreach ($responseData->validations?->dcv->toArray() ?? [] as $dcvEntry) {
            try {
                $panel = Panel::getPanelData($dcvEntry['commonName']);
                if ($dcvEntry['type'] == 'FILE') {
                    $result = FileControl::create(
                        [
                            'fileLocation' => $data['fileLocation'], // whole url,
                            'fileContents' => $data['fileContents']
                        ],
                        $panel
                    );

                    if ($result['status'] === 'success') {
                        $logs->addLog(
                            $this->p['userid'],
                            $this->p['serviceid'],
                            'success',
                            'The ' . $dcvEntry['commonName'] . ' domain has been verified using the file method.'
                        );
                        $revalidate = true;
                    }
                } elseif ($data['type'] == 'DNS') {
                    $result = DnsControl::generateRecord($data, $panel);
                    if ($result) {
                        $logs->addLog(
                            $this->p['userid'],
                            $this->p['serviceid'],
                            'success',
                            'The ' . $dcvEntry['commonName'] . ' domain has been verified using the dns method.'
                        );
                        $revalidate = true;
                    }
                }
            } catch (Exception $e) {
                $logs->addLog(
                    $this->p['userid'],
                    $this->p['serviceid'],
                    'error',
                    '[' . $dcvEntry['commonName']. '] Error:' . $e->getMessage()
                );
                continue;
            }
        }

        $sslService->setRemoteId($responseData->processId);
        $sslService->setConfigdataKey('private_key', null);
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
        $this->validateSanDomains();
        $this->validateSansDomainsWildcard();

        $mainDomain = ApiProvider::getInstance()
            ->getApi(CertificatesApi::class)
            ->decodeCsr($this->p['csr'])['commonName'];
        $domains = $mainDomain . PHP_EOL . $this->p['sanDomains'] . PHP_EOL . $this->p['sanDomainsWildcard'];
        $parseDomains = SansDomains::parseDomains($domains);
        $SSLStepTwoJS = new SSLStepTwoJS($this->p);
        $this->response(true, 'Approve Emails', $SSLStepTwoJS->fetchApprovalEmailsForSansDomains($parseDomains));
    }

    private function validateSanDomains()
    {
        $this->moduleBuildParams();
        $sansDomains = $this->p['sanDomains'];

        $sansDomains = SansDomains::parseDomains($sansDomains);

        $apiProductId = $this->serviceParams[ConfigOptions::API_PRODUCT_ID];

        $invalidDomains = Domains::getInvalidDomains($sansDomains);

        if (count($invalidDomains)) {
            throw new Exception(Lang::getInstance()->T('incorrectSans') . implode(', ', $invalidDomains));
        }

        $productBrandRepository = Products::getInstance();
        $productBrand = $productBrandRepository->getProduct(KeyToIdMapping::getIdByKey($apiProductId));
        $uniqueDomains = [];
        if ($sansDomains !== null && count($sansDomains) > 0) {
            if (in_array('WWW_INCLUDED', $productBrand->features)) {
                foreach ($sansDomains as $domain) {
                    // Remove 'www.' prefix if it exists
                    $normalizedDomain = preg_replace('/^www\./', '', $domain);
                    // Add the normalized domain to the array
                    $normalizedDomains[] = $normalizedDomain;
                }

                $uniqueDomains = array_unique($normalizedDomains);
            } else {
                $uniqueDomains = $sansDomains;
            }
        }

        $sansLimit = $this->getSansLimit();
        if (count($uniqueDomains) > $sansLimit) {
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

        $sansWildcardLimit = $this->getSansWildcardLimit();
        if (count($sansDomainsWildcard) > $sansWildcardLimit) {
            throw new Exception(Lang::getInstance()->T('exceededLimitOfSans'));
        }
    }

    private function getSansLimit()
    {
        $sanEnabledForWHMCSProduct = $this->serviceParams[ConfigOptions::PRODUCT_ENABLE_SAN] === 'on';
        if (!$sanEnabledForWHMCSProduct) {
            return 0;
        }
        $period = intval($this->serviceParams['configoptions'][ConfigOptions::OPTION_PERIOD][0]);
        $includedSans = (int)$this->serviceParams[ConfigOptions::PRODUCT_INCLUDED_SANS];
        $boughtSans = (int)$this->serviceParams['configoptions'][ConfigOptions::OPTION_SANS_COUNT . $period];
        return $includedSans + $boughtSans;
    }

    private function getSansWildcardLimit()
    {
        $sanEnabledForWHMCSProduct = $this->serviceParams[ConfigOptions::PRODUCT_ENABLE_SAN_WILDCARD] === 'on';
        if (!$sanEnabledForWHMCSProduct) {
            return 0;
        }
        $period = intval($this->serviceParams['configoptions'][ConfigOptions::OPTION_PERIOD][0]);
        $includedSans = (int)$this->serviceParams[ConfigOptions::PRODUCT_INCLUDED_SANS_WILDCARD];
        $boughtSans = (int)$this->serviceParams['configoptions'][ConfigOptions::OPTION_SANS_WILDCARD_COUNT . $period];
        return $includedSans + $boughtSans;
    }
}
