<?php

namespace AddonModule\RealtimeRegisterSsl\eServices\provisioning;

use AddonModule\RealtimeRegisterSsl\addonLibs\Lang;
use AddonModule\RealtimeRegisterSsl\eHelpers\Domains;
use AddonModule\RealtimeRegisterSsl\eHelpers\Invoice;
use AddonModule\RealtimeRegisterSsl\eHelpers\SansDomains;
use AddonModule\RealtimeRegisterSsl\eProviders\ApiProvider;
use AddonModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\KeyToIdMapping;
use AddonModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\Products;
use AddonModule\RealtimeRegisterSsl\eRepository\whmcs\config\Countries;
use AddonModule\RealtimeRegisterSsl\eRepository\whmcs\service\SSL;
use AddonModule\RealtimeRegisterSsl\eServices\ManagementPanel\Api\Panel\Panel;
use AddonModule\RealtimeRegisterSsl\eServices\ManagementPanel\Dns\DnsControl;
use AddonModule\RealtimeRegisterSsl\eServices\ManagementPanel\File\FileControl;
use AddonModule\RealtimeRegisterSsl\eServices\ScriptService;
use AddonModule\RealtimeRegisterSsl\eServices\TemplateService;
use AddonModule\RealtimeRegisterSsl\models\apiConfiguration\Repository;
use AddonModule\RealtimeRegisterSsl\models\logs\Repository as LogsRepo;
use AddonModule\RealtimeRegisterSsl\models\whmcs\product\Product;
use AddonModule\RealtimeRegisterSsl\models\whmcs\service\Service;
use Exception;
use Illuminate\Database\Capsule\Manager as Capsule;
use SandwaveIo\RealtimeRegister\Api\CertificatesApi;
use SandwaveIo\RealtimeRegister\Api\ProcessesApi;

class ClientReissueCertificate
{
    /**
     *
     * @var array
     */
    private $p;

    /**
     *
     * @var array
     */
    private $get;

    /**
     *
     * @var array
     */
    private $post;

    /**
     *
     * @var array
     */
    private $vars;

    /**
     *
     * @var \AddonModule\RealtimeRegisterSsl\eModels\whmcs\service\SSL
     */
    private $sslService;

    /**
     *
     * @var array
     */
    private $orderStatus;

    public const STEP_ONE = 'pages/reissue/stepOne';
    public const STEP_TWO = 'pages/reissue/stepTwo';
    public const SUCCESS = 'pages/reissue/stepSuccess';

    public function __construct(&$params, &$post, &$get)
    {
        $this->p = &$params;
        $this->get = &$get;
        $this->post = &$post;
        $this->vars = [];
        $this->vars['errors'] = [];
    }

    public function run()
    {
        SansDomains::decodeSanAprroverEmailsAndMethods($_POST);
        $this->setMainDomainDcvMethod($_POST);
        $this->setSansDomainsDcvMethod($_POST);
        return $this->miniControler();
    }

    private function miniControler()
    {
        try {
            $this->validateService();
        } catch (Exception $ex) {
            return '- ' . \AddonModule\RealtimeRegisterSsl\eHelpers\Exception::e($ex);
        }
        if (isset($this->post['stepOneForm'])) {
            try {
                $this->stepOneForm();
                $ssl = new SSL();
                $ssldata = $ssl->getByServiceId($this->p['serviceid']);
                $this->setApproverData($ssldata);
                $this->vars['countries'] = Countries::getInstance()->getCountriesForAddonDropdown();
                return $this->build(self::STEP_TWO);
            } catch (Exception $ex) {
                $this->vars['errors'][] = \AddonModule\RealtimeRegisterSsl\eHelpers\Exception::e($ex);
            }
        }

        if (isset($this->post['stepTwoForm'])) {
            try {
                $this->stepTwoForm();
                global $CONFIG;
                $this->vars['actuallink'] = $CONFIG['SystemURL'] . '/clientarea.php?action=productdetails&id='
                    . $_GET['id'];
                return $this->build(self::SUCCESS);
            } catch (Exception $ex) {
                $this->vars['errors'][] = \AddonModule\RealtimeRegisterSsl\eHelpers\Exception::e($ex);
            }
        }

        // Display csr generator
        $apiConf = (new Repository())->get();
        $displayCsrGenerator = $apiConf->display_csr_generator;
        $countriesForGenerateCsrForm = Countries::getInstance()->getCountriesForAddonDropdown();

        // Get selected default country for CSR Generator
        $defaultCsrGeneratorCountry = ($displayCsrGenerator) ? $apiConf->default_csr_generator_country : '';
        if (
            key_exists($defaultCsrGeneratorCountry, $countriesForGenerateCsrForm) && $defaultCsrGeneratorCountry != null
        ) {
            // Get country name
            $elementValue = $countriesForGenerateCsrForm[$defaultCsrGeneratorCountry];
            // Remove country from list
            unset($countriesForGenerateCsrForm[$defaultCsrGeneratorCountry]);
            // Insert default country on the begin of countries list
            $countriesForGenerateCsrForm = array_merge(
                [$defaultCsrGeneratorCountry => $elementValue],
                $countriesForGenerateCsrForm
            );
        }

        $this->vars['generateCsrIntegrationCode'] = ($displayCsrGenerator) ? ScriptService::getGenerateCsrModalScript(
            $this->p['serviceid'],
            json_encode([]),
            $countriesForGenerateCsrForm
        ) : '';
        $this->vars['serviceID'] = $this->p['serviceid'];

        $this->vars['sansLimit'] = $this->getSansLimit();
        $this->vars['sansLimitWildCard'] = $this->getSansLimitWildcard();

        $ssl = new SSL();
        $ssldata = $ssl->getByServiceId($this->p['serviceid']);
        $this->vars['csrreissue'] = $ssldata->configdata->csr;
        $sandetails = (array)$ssl->getByServiceId($this->p['serviceid'])->getSanDomains();
        $this->vars['sandetails'] = $sandetails;
        $this->vars['sans_domains'] = $sandetails['sans_domains'];

        $sanSingle = [];
        $sanWildcard = [];

        $this->vars['privKey'] = '';
        if (isset($ssldata->configdata->private_key) && !empty($ssldata->configdata->private_key)) {
            $this->vars['privKey'] = $ssldata->configdata->private_key;
        }

        $allSans = $ssldata->configdata->san_details;

        foreach ($allSans as $san) {
            if (strpos($san->san_name, '*.') !== false) {
                $sanWildcard[] = $san->san_name;
            } else {
                $sanSingle[] = $san->san_name;
            }
        }

        $sanSingle = implode(PHP_EOL, $sanSingle);
        $sanWildcard = implode(PHP_EOL, $sanWildcard);

        if (!isset($this->vars['sandetails']['wildcard_san']) || empty($this->vars['sandetails']['wildcard_san'])) {
            $this->vars['sandetails']['sans_domains'] = $sanSingle;
            $this->vars['sandetails']['wildcard_san'] = $sanWildcard;
        }

        return $this->build(self::STEP_ONE);
    }

    private function setApproverData(\AddonModule\RealtimeRegisterSsl\eModels\whmcs\service\SSL $sslData) {
        if (!str_contains($sslData->getProductId(), "ev") && !str_contains($sslData->getProductId(), "ov")) {
            $this->vars['extraValidation'] = false;
            return;
        }
        $this->vars['extraValidation'] = true;
        $configData = $sslData->configdata;

        $cert = ApiProvider::getInstance()
            ->getApi(CertificatesApi::class)
            ->getCertificate($sslData->getCertificateId());

        $this->vars['firstname'] = $configData->firstname;
        $this->vars['lastname'] = $configData->lastname;
        $this->vars['email'] = $configData->email;
        $this->vars['phonenumber'] = $configData->phonenumber;
        $this->vars['jobtitle'] = $configData->jobtitle;
        $this->vars['orgname'] = $cert->organization;
        $this->vars['city'] = $cert->city;
        $this->vars['state'] = $cert->state;
        $this->vars['country'] = $cert->country;
        $this->vars['address'] = implode("\n", $cert->addressLine);
        $this->vars['postcode'] = $cert->postalCode;
      //  $this->vars[]
    }


    private function setMainDomainDcvMethod($post)
    {
        $this->post['dcv_method'] = $post['dcvmethodMainDomain'];
    }

    private function setSansDomainsDcvMethod($post)
    {
        if (isset($post['dcvmethod']) && is_array($post['dcvmethod'])) {
            $this->post['sansDomainsDcvMethod'] = $post['dcvmethod'];
        }
    }

    private function stepOneForm()
    {
        $this->validateSanDomains();
        $this->validateSansDomainsWildcard();
        $decodeCSR = ApiProvider::getInstance()->getApi(CertificatesApi::class)->decodeCsr($this->post['csr']);

        $_SESSION['decodeCSR'] = $decodeCSR;

        $service = new Service($this->p['serviceid']);
        $product = new Product($service->productID);

        $mainDomain = $decodeCSR['commonName'];
        $domains = $mainDomain . PHP_EOL . $this->post['sans_domains'];
        $parseDomains = SansDomains::parseDomains(strtolower($domains));
        $domainsWildcard = $this->post['sans_domains_wildcard'];
        $parseDomainsWildcard = SansDomains::parseDomains(strtolower($domainsWildcard));
        $parseDomains = array_merge($parseDomains, $parseDomainsWildcard);
        $SSLStepTwoJS = new SSLStepTwoJS($this->p);
        $this->vars['approvalEmails'] = json_encode($SSLStepTwoJS->fetchApprovalEmailsForSansDomains($parseDomains));
        $this->vars['brand'] = json_encode($this->getCertificateBrand());
        if (isset($this->post['privateKey'])) {
            $this->vars['privateKey'] = $this->post['privateKey'];
        }

        $disabledValidationMethods = [];

        $productssl = false;
        $checkTable = Capsule::schema()->hasTable(Products::REALTIMEREGISTERSSL_PRODUCT_BRAND);
        if ($checkTable) {
            if (Capsule::schema()->hasColumn(Products::REALTIMEREGISTERSSL_PRODUCT_BRAND, 'data')) {
                $productsslDB = Capsule::table(Products::REALTIMEREGISTERSSL_PRODUCT_BRAND)->where(
                    'pid',
                    $product->configuration()->text_name
                )->first();
                if (isset($productsslDB->data)) {
                    $productssl['product'] = json_decode($productsslDB->data, true);
                }
            }
        }

        if (!$productssl) {
            /** @var CertificatesApi $certificatesApi */
            $certificatesApi = ApiProvider::getInstance()->getApi(CertificatesApi::class);
            $productssl = $certificatesApi->getProduct($product->configuration()->text_name)->toArray();
        }

        $this->vars['disabledValidationMethods'] = json_encode($disabledValidationMethods);
    }

    private function stepTwoForm()
    {
        if (isset($_SESSION['decodeCSR']) && !empty($_SESSION['decodeCSR'])) {
            $decodedCSR = $_SESSION['decodeCSR'];
        } else {
            $decodedCSR = ApiProvider::getInstance()->getApi(CertificatesApi::class)->decodeCsr($this->post['csr']);
        }
        $commonName = $decodedCSR['commonName'];
        $dcv = [];
        $dcv[] = [
            "commonName" => $commonName,
            "type" => self::getDcvMethod($this->post['dcv_method']),
            "email" => $this->post['approveremail']
        ];
        $csr = $this->post['csr'];

        $sansDomains = [];

        if ($this->getSansLimit()) {
            $this->validateSanDomains();
            $sansDomains = SansDomains::parseDomains($this->post['sans_domains']);
            $sansDomainsWildcard = SansDomains::parseDomains($this->post['sans_domains_wildcard']);
            $sansDomains = array_merge($sansDomains, $sansDomainsWildcard);

            //if entered san is the same as main domain
            if (count($sansDomains) != count($_POST['approveremails'])) {
                foreach ($sansDomains as $key => $domain) {
                    if ($decodedCSR['commonName'] == $domain) {
                        unset($sansDomains[$key]);
                    }
                }
            }
            $data['dns_names'] = implode(',', $sansDomains);


            if (!empty($sanDcvMethods = $this->getSansDomainsValidationMethods())) {
                $i = 0;
                foreach ($_POST['approveremails'] as $domain => $approveremail) {
                    $dcv[] = [
                        "commonName" => $domain,
                        "type" => self::getDcvMethod(strtolower($sanDcvMethods[$i])),
                        "email" => $approveremail
                    ];
                    $i++;
                }
            }
        }

        $service = new Service($this->p['serviceid']);
        $product = new Product($service->productID);

        $productssl = false;
        $checkTable = Capsule::schema()->hasTable(Products::REALTIMEREGISTERSSL_PRODUCT_BRAND);
        if ($checkTable) {
            if (Capsule::schema()->hasColumn(Products::REALTIMEREGISTERSSL_PRODUCT_BRAND, 'data')) {
                $productsslDB = Capsule::table(Products::REALTIMEREGISTERSSL_PRODUCT_BRAND)->where(
                    'pid',
                    $product->configuration()->text_name
                )->first();
                if (isset($productsslDB->data)) {
                    $productssl['product'] = json_decode($productsslDB->data, true);
                }
            }
        }

        $approver = null;
        $organization = null;
        $address = null;
        $postalCode = null;
        $city = null;
        $state = null;

        if ($this->post['extraValidation']) {
            $organization = $this->post['orgname'];
            $address = $this->post['address'];
            $postalCode = $this->post['postcode'];
            $city = $this->post['city'];
            $state = $this->post['state'];
            $approver = [
                "firstName" => $this->post['firstname'],
                "lastName" => $this->post['lastname'],
                "jobTitle" => $this->post['jobtitle'],
                "email" => $this->post['email'],
                "voice" => str_replace(" ", "",
                    '+' . $this->post['country-calling-code-phonenumber'] . '.' . $this->post['phonenumber'])
            ];
        }



        $reissueData = ApiProvider::getInstance()
            ->getApi(CertificatesApi::class)
            ->reissueCertificate(
                $this->sslService->getCertificateId(),
                $csr,
                $sansDomains,
                $organization,
                null,
                $address,
                $postalCode,
                $city,
                null,
                $approver,
                null,
                null,
                $dcv,
                $commonName,
            null,
                $state);
        /** @var ProcessesApi $processesApi */
        $this->sslService->setRemoteId($reissueData->processId);
        $processesApi = ApiProvider::getInstance()->getApi(ProcessesApi::class);
        $orderDetails = $processesApi->info($this->sslService->getRemoteId())->toArray();

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

        //save private key
        if (isset($_POST['privateKey']) && $_POST['privateKey'] != null) {
            $privKey = decrypt($_POST['privateKey']);
            $GenerateSCR = new GenerateCSR($this->p, $_POST);
            $GenerateSCR->savePrivateKeyToDatabase($this->p['serviceid'], $privKey);
        }

        //update domain column in tblhostings
        $service = new Service($this->p['serviceid']);
        $service->save(['domain' => $decodedCSR['commonName']]);

        $this->sslService->setDomain($decodedCSR['commonName']);
        $this->sslService->setSSLStatus('processing');
        $this->sslService->setCrt(null);
        $this->sslService->setCa(null);
        $this->sslService->setConfigdataKey('servertype', "-1");
        $this->sslService->setConfigdataKey('csr', $csr);
        $this->sslService->setConfigdataKey('approveremail', $dcv[0]['email']);
        $this->sslService->setConfigdataKey('private_key', $_POST['privateKey']);
        $this->sslService->setApproverEmails(
            array_filter(array_map(function ($entry) {return $entry['email'];},  $orderDetails['validations'] ?? [])));
        $this->sslService->setSanDetails(
            array_filter(array_map(function ($entry) {return $entry['commonName'];},  $orderDetails['validations'] ?? []),
                function ($entry) {return $entry['commonName'] != $this->sslService->getDomain();}
            ));
        $this->sslService->save();

        try {
            Invoice::insertDomainInfoIntoInvoiceItemDescription($this->p['serviceid'], $decodedCSR['commonName'], true);
        } catch (Exception $e) {
        }
    }

    private static function getDcvMethod(string $dcvMethod) : string {
        return $dcvMethod == 'http' ? 'FILE' : strtoupper($dcvMethod);
    }

    private function getSansDomainsValidationMethods()
    {
        $data = [];
        foreach ($this->post['sansDomainsDcvMethod'] as $newMethod) {
            $data[] = $newMethod;
        }
        return $data;
    }

    private function getCertificateBrand()
    {
        if (!empty($this->p[ConfigOptions::API_PRODUCT_ID])) {
            $apiRepo = new Products();
            $apiProduct = $apiRepo->getProduct(KeyToIdMapping::getIdByKey($this->p[ConfigOptions::API_PRODUCT_ID]));
            return $apiProduct->brand;
        }
    }

    private function validateSanDomains()
    {
        $sansDomains = $this->post['sans_domains'];
        $sansDomains = SansDomains::parseDomains($sansDomains);

        $apiProductId = $this->p[ConfigOptions::API_PRODUCT_ID];

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

    private function validateSansDomainsWildcard()
    {
        $sansDomainsWildcard = $this->post['sans_domains_wildcard'];
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

        $includedSans = (int)$this->p[ConfigOptions::PRODUCT_INCLUDED_SANS_WILDCARD];
        $boughtSans = (int)$this->p['configoptions']['sans_wildcard_count'];

        $sansLimit = $includedSans + $boughtSans;
        if (count($sansDomainsWildcard) > $sansLimit) {
            throw new Exception(Lang::T('sanLimitExceededWildcard'));
        }
    }

    private function validateService()
    {
        $ssl = new SSL();
        $this->sslService = $ssl->getByServiceId($this->p['serviceid']);
        if (is_null($this->sslService)) {
            throw new Exception(Lang::getInstance()->T('createNotInitialized'));
        }

        if (!in_array($this->sslService->configdata->ssl_status, ['active', 'COMPLETED'])) {
            throw new Exception(Lang::getInstance()->T('notAllowToReissue'));
        }
    }

    private function build($template)
    {

        $this->vars['error'] = implode('<br>', $this->vars['errors']);
        $content = TemplateService::buildTemplate($template, $this->vars);
        return [
            'templatefile' => 'main',
            'vars' => ['content' => $content],
        ];
    }

    private function getSansLimit()
    {
        $sanEnabledForWHMCSProduct = $this->p[ConfigOptions::PRODUCT_ENABLE_SAN] === 'on';
        if (!$sanEnabledForWHMCSProduct) {
            return 0;
        }
        $includedSans = (int)$this->p[ConfigOptions::PRODUCT_INCLUDED_SANS];
        $boughtSans = (int)$this->p['configoptions'][ConfigOptions::OPTION_SANS_COUNT];
        return $includedSans + $boughtSans;
    }

    private function getSansLimitWildcard()
    {
        $sanEnabledForWHMCSProduct = $this->p[ConfigOptions::PRODUCT_ENABLE_SAN_WILDCARD] === 'on';
        if (!$sanEnabledForWHMCSProduct) {
            return 0;
        }
        $includedSans = (int)$this->p[ConfigOptions::PRODUCT_INCLUDED_SANS_WILDCARD];
        $boughtSans = (int)$this->p['configoptions']['sans_wildcard_count'];
        return $includedSans + $boughtSans;
    }
}
