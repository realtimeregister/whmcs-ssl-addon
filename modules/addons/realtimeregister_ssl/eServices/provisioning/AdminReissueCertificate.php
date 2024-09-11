<?php

namespace AddonModule\RealtimeRegisterSsl\eServices\provisioning;

use Exception;
use AddonModule\RealtimeRegisterSsl\eHelpers\Domains;
use AddonModule\RealtimeRegisterSsl\eHelpers\Invoice;
use AddonModule\RealtimeRegisterSsl\eHelpers\SansDomains;
use AddonModule\RealtimeRegisterSsl\eProviders\ApiProvider;
use AddonModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\KeyToIdMapping;
use AddonModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\Products;
use AddonModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\WebServers;
use AddonModule\RealtimeRegisterSsl\eRepository\whmcs\service\SSL;
use AddonModule\RealtimeRegisterSsl\eServices\ManagementPanel\Api\Panel\Panel;
use AddonModule\RealtimeRegisterSsl\eServices\ManagementPanel\Dns\DnsControl;
use AddonModule\RealtimeRegisterSsl\eServices\ManagementPanel\File\FileControl;
use AddonModule\RealtimeRegisterSsl\addonLibs\Lang;
use AddonModule\RealtimeRegisterSsl\models\logs\Repository as LogsRepo;
use AddonModule\RealtimeRegisterSsl\models\whmcs\product\Product;
use AddonModule\RealtimeRegisterSsl\models\whmcs\service\Service;
use SandwaveIo\RealtimeRegister\Api\CertificatesApi;
use SandwaveIo\RealtimeRegister\Api\ProcessesApi;
use function ModuleBuildParams;

class AdminReissueCertificate extends Ajax
{
    private $p;
    private $serviceParams;

    function __construct(&$params)
    {
        $this->p = &$params;
    }

    public function run()
    {
        try {
            return $this->miniControler();
        } catch (Exception $ex) {
            $this->response(false, $ex->getMessage());
        }
    }

    private function miniControler()
    {
        if ($this->p['action'] === 'reissueCertificate') {
            return $this->reissueCertificate();
        }

        if ($this->p['action'] === 'webServers') {
            return $this->webServers();
        }

        if ($this->p['action'] === 'getApprovals') {
            return $this->getApprovals();
        }
    }

    private function reissueCertificate()
    {
        $this->validateSanDomains();
        $this->validateServerType();

        $sslRepo    = new SSL();
        $sslService = $sslRepo->getByServiceId($this->p['serviceId']);

        if (is_null($sslService)) {
            throw new Exception('Create has not been initialized.');
        }

        if ($this->p['userID'] != $sslService->userid) {
            throw new Exception('An error occurred.');
        }

        $data = [
            'webserver_type'  => $this->p['webServer'],
            'csr'             => $this->p['csr'],
            'approver_email' => $this->p['approveremail'],
        ];

        $sansDomains = [];

        $sanEnabledForWHMCSProduct = $this->serviceParams[ConfigOptions::PRODUCT_ENABLE_SAN] === 'on';

        if ($sanEnabledForWHMCSProduct && count($_POST['approveremails'])) {
            $this->validateSanDomains();
            $sansDomains             = SansDomains::parseDomains($this->p['sanDomains']);
            $sansDomainsWildCard     = SansDomains::parseDomains($this->p['sanDomainsWildcard']);

            $sansDomains = array_merge($sansDomains, $sansDomainsWildCard);

            $approverEmails = $this->p['approveremails'];

            $data['approver_email'] = $approverEmails[0];
            unset($approverEmails[0]);
            $approverEmailsText = implode(',', $approverEmails);

            $data['dns_names']       = implode(',', $sansDomains);
            $data['approver_emails'] = $approverEmailsText;
        }

        $service = new Service($this->p['serviceId']);
        $product = new Product($service->productID);

        if($product->configuration()->text_name == '144')
        {
            $sansDomains             = SansDomains::parseDomains($this->p['sanDomains']);

            $data['dns_names'] = implode(',', $sansDomains);
            $data['approver_emails'] = $sslService->configdata->dcv_method;

            for($i=0;$i<count($sansDomains)-1;$i++)
            {
                $data['approver_emails'] .= ','.$sslService->configdata->dcv_method;
            }
            $data['dcv_method'] = $sslService->configdata->dcv_method;
        }

        /** @var ProcessesApi $processesApi */
        $processesApi = ApiProvider::getInstance()->getApi(ProcessesApi::class);
        $orderStatus = $processesApi->get($sslService->remoteid);

        $singleDomainsCount = $orderStatus['single_san_count'];
        $wildcardDomainsCount = $orderStatus['wildcard_san_count'];

        $newSanDomainSingleCount = count(explode(PHP_EOL,$this->p['sanDomains']));
        $newSanDomainWildcardCount = count(explode(PHP_EOL,$this->p['sanDomainsWildcard']));

        if(!empty($this->p['sanDomains']) || !empty($this->p['sanDomainsWildcard'])) {
            if ($newSanDomainSingleCount > $singleDomainsCount || $newSanDomainWildcardCount > $wildcardDomainsCount) {
                $singleToAdd = $newSanDomainSingleCount - $singleDomainsCount;
                if ($singleToAdd < 0) {
                    $singleToAdd = 0;
                }
                $wildcardToAdd = $newSanDomainWildcardCount - $wildcardDomainsCount;
                if ($wildcardToAdd < 0) {
                    $wildcardToAdd = 0;
                }
                $allToAdd = $singleToAdd + $wildcardToAdd;

                if ($singleToAdd <= 0) {
                    $allToAdd = 0;
                }

                ApiProvider::getInstance()->getApi()->addSslSan(
                    $sslService->remoteid,
                    $allToAdd,
                    $singleToAdd,
                    $wildcardToAdd
                );
            }
        }

        $reissueData = ApiProvider::getInstance()->getApi()->reIssueOrder($sslService->remoteid, $data);
        /** @var ProcessesApi $processesApi */
        $processesApi = ApiProvider::getInstance()->getApi(ProcessesApi::class);
        $orderDetails = $processesApi->get($sslService->remoteid);

        $logs = new LogsRepo();

        foreach ($orderDetails['validations']['dcv'] as $data) {
            try {
                $panel = Panel::getPanelData($data['commonName']);

                if ($data['type'] == 'FILE') {
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
                            'The ' . $service->domain . ' domain has been verified using the file method.'
                        );
                        $revalidate = true;
                    }
                } elseif ($data['type'] == 'DNS') {
                    if ($data['dnsType'] == 'CNAME') {

                        $result = DnsControl::generateRecord($data, $panel);
                        if ($result) {
                            $logs->addLog(
                                $this->p['userid'],
                                $this->p['serviceid'],
                                'success',
                                'The ' . $service->domain . ' domain has been verified using the dns method.'
                            );
                            $revalidate = true;
                        }
                    }
                }
            } catch (Exception $e) {
                $logs->addLog(
                    $this->p['userid'],
                    $this->p['serviceid'],
                    'error',
                    '[' . $service->domain . '] Error:' . $e->getMessage()
                );
                continue;
            }
        }

        $sslService->setConfigdataKey('servertype', $data['webserver_type']);
        $sslService->setConfigdataKey('csr', $data['csr']);
        $sslService->setConfigdataKey('approveremail', $data['approver_email']);
        $sslService->setApproverEmails($data['approver_emails']);
        $sslService->setSansDomains($data['dns_names']);
        $sslService->save();

        try {
            $decodedCSR   = ApiProvider::getInstance()->getApi(CertificatesApi::class)->decodeCsr($this->p['csr']);
            Invoice::insertDomainInfoIntoInvoiceItemDescription(
                $this->p['serviceId'],
                $decodedCSR['csrResult']['CN'],
                true
            );

            $service = new Service($this->p['serviceId']);
            $service->save(['domain' => $decodedCSR['csrResult']['CN']]);

            $configDataUpdate = new UpdateConfigData($sslService);
            $configDataUpdate->run();
        } catch(Exception $e) {
        }

        $this->response(true, 'Certificate was successfully reissued.');
    }

    private function webServers()
    {
        $this->moduleBuildParams();
        $apiProductId  = $this->serviceParams[ConfigOptions::API_PRODUCT_ID];
        $apiRepo       = new Products();
        $apiProduct    = $apiRepo->getProduct($apiProductId);
        $apiWebServers = WebServers::getAll($apiProduct->getWebServerTypeId());
        $this->response(true, 'Web Servers', $apiWebServers);

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
        $this->validateServerType();
        $decodeCSR = ApiProvider::getInstance()->getApi(CertificatesApi::class)->decodeCsr($this->p['csr']);

        if(isset($decodeCSR['csrResult']['errorMessage'])){
            throw new Exception($decodeCSR['csrResult']['errorMessage']);
        }
        $mainDomain   = $decodeCSR['csrResult']['CN'];
        $domains      = $mainDomain . PHP_EOL . $this->p['sanDomains'].PHP_EOL.$this->p['sanDomainsWildcard'];
        $parseDomains = SansDomains::parseDomains($domains);
        $SSLStepTwoJS = new SSLStepTwoJS($this->p);
        $this->response(true, 'Approve Emails', $SSLStepTwoJS->fetchApprovalEmailsForSansDomains($parseDomains));
    }

    private function validateSanDomains()
    {
        $this->moduleBuildParams();
        $sansDomains = $this->p['sanDomains'];
        $sansDomains = SansDomains::parseDomains($sansDomains);

        $apiProductId     = $this->serviceParams[ConfigOptions::API_PRODUCT_ID];

        $invalidDomains = Domains::getInvalidDomains($sansDomains, in_array($apiProductId, [100, 99, 63]));

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

        $sansLimit    = $this->getSansLimit();
        if (count($uniqueDomains) > $sansLimit) {
            throw new Exception(Lang::getInstance()->T('exceededLimitOfSans'));
        }
    }

    private function validateSansDomainsWildcard()
    {
        $sansDomainsWildcard = $this->p['sanDomainsWildcard'];
        $sansDomainsWildcard = SansDomains::parseDomains($sansDomainsWildcard);

        foreach($sansDomainsWildcard as $domain) {
            $check = substr($domain, 0,2);
            if($check != '*.')
            {
                throw new Exception('SAN\'s Wildcard are incorrect');
            }
            $domaincheck = Domains::validateDomain(substr($domain, 2));
            if($domaincheck !== true)
            {
                throw new Exception('SAN\'s Wildcard are incorrect');
            }
        }

        $includedSans = (int) $this->serviceParams[ConfigOptions::PRODUCT_INCLUDED_SANS_WILDCARD];
        $boughtSans   = (int) $this->serviceParams['configoptions']['sans_wildcard_count'];

        $sansLimit = $includedSans + $boughtSans;
        if (count($sansDomainsWildcard) > $sansLimit) {
            throw new Exception(Lang::T('sanLimitExceededWildcard'));
        }
    }

    private function validateServerType()
    {
        if($this->p['webServer'] == 0) {
            throw new Exception('You must select client server type');
        }
    }

    private function getSansLimit()
    {
        $sanEnabledForWHMCSProduct = $this->serviceParams[ConfigOptions::PRODUCT_ENABLE_SAN] === 'on';
        if (!$sanEnabledForWHMCSProduct) {
            return 0;
        }
        $includedSans = (int) $this->serviceParams[ConfigOptions::PRODUCT_INCLUDED_SANS];
        $boughtSans   = (int) $this->serviceParams['configoptions'][ConfigOptions::OPTION_SANS_COUNT];
        return $includedSans + $boughtSans;
    }

    private function getSansLimitWildcard()
    {
        $includedSans = (int) $this->serviceParams[ConfigOptions::PRODUCT_INCLUDED_SANS_WILDCARD];
        $boughtSans   = (int) $this->serviceParams['configoptions']['sans_wildcard_count'];
        return $includedSans + $boughtSans;
    }
}
