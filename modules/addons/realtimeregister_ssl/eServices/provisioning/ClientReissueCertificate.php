<?php

namespace MGModule\RealtimeRegisterSsl\eServices\provisioning;

use Exception;
use Illuminate\Database\Capsule\Manager as Capsule;
use MGModule\DNSManager2\addon;
use MGModule\DNSManager2\loader;
use MGModule\DNSManager2\mgLibs\custom\helpers\DomainHelper;
use MGModule\RealtimeRegisterSsl\eHelpers\Domains;
use MGModule\RealtimeRegisterSsl\eHelpers\Invoice;
use MGModule\RealtimeRegisterSsl\eHelpers\SansDomains;
use MGModule\RealtimeRegisterSsl\eProviders\ApiProvider;
use MGModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\KeyToIdMapping;
use MGModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\Products;
use MGModule\RealtimeRegisterSsl\eRepository\whmcs\config\Countries;
use MGModule\RealtimeRegisterSsl\eRepository\whmcs\service\SSL;
use MGModule\RealtimeRegisterSsl\eServices\Deploy\API\Dns\Manage;
use MGModule\RealtimeRegisterSsl\eServices\FlashService;
use MGModule\RealtimeRegisterSsl\eServices\ScriptService;
use MGModule\RealtimeRegisterSsl\eServices\TemplateService;
use MGModule\RealtimeRegisterSsl\mgLibs\Lang;
use MGModule\RealtimeRegisterSsl\models\apiConfiguration\Repository;
use MGModule\RealtimeRegisterSsl\models\whmcs\product\Product;
use MGModule\RealtimeRegisterSsl\models\whmcs\service\Service;
use SandwaveIo\RealtimeRegister\Api\CertificatesApi;
use SandwaveIo\RealtimeRegister\Api\ProcessesApi;

class ClientReissueCertificate
{
    // allow *.domain.com as SAN for products
    public const PRODUCTS_WITH_ADDITIONAL_SAN_VALIDATION = [100, 99, 63];
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
     * @var \MGModule\RealtimeRegisterSsl\eModels\whmcs\service\SSL
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
            return '- ' . \MGModule\RealtimeRegisterSsl\eHelpers\Exception::e($ex);
        }
        if (isset($this->post['stepOneForm'])) {
            try {
                $this->stepOneForm();
                return $this->build(self::STEP_TWO);
            } catch (Exception $ex) {
                $this->vars['errors'][] = \MGModule\RealtimeRegisterSsl\eHelpers\Exception::e($ex);
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
                $this->vars['errors'][] = \MGModule\RealtimeRegisterSsl\eHelpers\Exception::e($ex);
            }
        }

        //dsiplay csr generator
        $apiConf = (new Repository())->get();
        $displayCsrGenerator = $apiConf->display_csr_generator;
        $countriesForGenerateCsrForm = Countries::getInstance()->getCountriesForMgAddonDropdown();

        //get selected default country for CSR Generator
        $defaultCsrGeneratorCountry = ($displayCsrGenerator) ? $apiConf->default_csr_generator_country : '';
        if (
            key_exists($defaultCsrGeneratorCountry, $countriesForGenerateCsrForm) && $defaultCsrGeneratorCountry != null
        ) {
            //get country name
            $elementValue = $countriesForGenerateCsrForm[$defaultCsrGeneratorCountry];
            //remove country from list
            unset($countriesForGenerateCsrForm[$defaultCsrGeneratorCountry]);
            //insert default country on the begin of countries list
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

        $this->loadServerList();
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


    private function setMainDomainDcvMethod($post)
    {
        $this->post['dcv_method'] = $post['dcvmethodMainDomain'];
    }

    private function setSansDomainsDcvMethod($post)
    {
        if (isset($post['dcvmethod']) && is_array($post['dcvmethod'])) {
            $this->post['sansDomansDcvMethod'] = $post['dcvmethod'];
        }
    }

    private function stepOneForm()
    {
        $this->validateWebServer();
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
        $apiConf = (new Repository())->get();

        $productssl = false;
        $checkTable = Capsule::schema()->hasTable(Products::MGFW_REALTIMEREGISTERSSL_PRODUCT_BRAND);
        if ($checkTable) {
            if (Capsule::schema()->hasColumn(Products::MGFW_REALTIMEREGISTERSSL_PRODUCT_BRAND, 'data')) {
                $productsslDB = Capsule::table(Products::MGFW_REALTIMEREGISTERSSL_PRODUCT_BRAND)->where(
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

//        foreach ($SSLStepTwoJS->fetchApprovalEmailsForSansDomains($parseDomains) as $sandomain => $approverEmails) {
//            if (str_contains($sandomain, '*.')) {
//                $disabledValidationMethods[] = 'http';
//            }
//        }

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

//        if ($product->configuration()->text_name == '144') {
//            $sansDomains = SansDomains::parseDomains($this->post['sans_domains']);
//
//            $data['dns_names'] = implode(',', $sansDomains);
//            $data['approver_emails'] = strtolower($_POST['dcvmethodMainDomain']);
//
//            foreach ($_POST['dcvmethod'] as $method) {
//                $data['approver_emails'] .= ',' . strtolower($method);
//            }
//        }

        $productssl = false;
        $checkTable = Capsule::schema()->hasTable(Products::MGFW_REALTIMEREGISTERSSL_PRODUCT_BRAND);
        if ($checkTable) {
            if (Capsule::schema()->hasColumn(Products::MGFW_REALTIMEREGISTERSSL_PRODUCT_BRAND, 'data')) {
                $productsslDB = Capsule::table(Products::MGFW_REALTIMEREGISTERSSL_PRODUCT_BRAND)->where(
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
            $productssl = $certificatesApi->getProduct($product->configuration()->text_name);
        }

        $processesApi = ApiProvider::getInstance()->getApi(ProcessesApi::class);

        $reissueData = ApiProvider::getInstance()
            ->getApi(CertificatesApi::class)
            ->reissueCertificate(
                $this->sslService->getCertificateId(),
                $csr,
                $sansDomains,
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                $dcv,
                $commonName);
        /** @var ProcessesApi $processesApi */
        $this->sslService->setRemoteId($reissueData->processId);
        $processesApi = ApiProvider::getInstance()->getApi(ProcessesApi::class);
        $orderDetails = $processesApi->info($this->sslService->getRemoteId())->toArray();

        // TODO add dns management
        // dns manager
        sleep(2);
        $dnsmanagerfile = dirname(
                dirname(dirname(dirname(dirname(__DIR__))))
            ) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'dnsmanager.php';
        $checkTable = Capsule::schema()->hasTable('dns_manager2_zone');
        if (file_exists($dnsmanagerfile) && $checkTable !== false) {
            $zoneDomain = $decodedCSR['commonName'];
            $loaderDNS = dirname(
                    dirname(dirname(dirname(dirname(__DIR__))))
                ) . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'addons'
                . DIRECTORY_SEPARATOR . 'DNSManager2' . DIRECTORY_SEPARATOR . 'loader.php';
            if (file_exists($loaderDNS)) {
                require_once $loaderDNS;
                $loader = new loader();
                addon::I(true);
                $helper = new DomainHelper($decodedCSR['commonName']);
                $zoneDomain = $helper->getDomainWithTLD();
            }

            $records = [];
            if (
                isset($orderDetails['approver_method']['dns']['record'])
                && !empty($orderDetails['approver_method']['dns']['record'])
            ) {
                if (strpos($orderDetails['approver_method']['dns']['record'], 'CNAME') !== false) {
                    $dnsrecord = explode("CNAME", $orderDetails['approver_method']['dns']['record']);
                    $records[] = [
                        'name' => trim(rtrim($dnsrecord[0])) . '.',
                        'type' => 'CNAME',
                        'ttl' => '3600',
                        'data' => trim(rtrim($dnsrecord[1]))
                    ];
                } else {
                    $dnsrecord = explode("IN   TXT", $orderDetails['approver_method']['dns']['record']);
                    $length = strlen(trim(rtrim($dnsrecord[1])));
                    $records[] = [
                        'name' => trim(rtrim($dnsrecord[0])) . '.',
                        'type' => 'TXT',
                        'ttl' => '14440',
                        'data' => substr(trim(rtrim($dnsrecord[1])), 1, $length - 2)
                    ];
                }

                $zone = Capsule::table('dns_manager2_zone')->where('name', $zoneDomain)->first();
                if (!isset($zone->id) || empty($zone->id)) {
                    $postfields = [
                        'action' => 'dnsmanager',
                        'dnsaction' => 'createZone',
                        'zone_name' => $zoneDomain,
                        'type' => '2',
                        'relid' => $this->p['serviceid'],
                        'zone_ip' => '',
                        'userid' => $this->p['userid']
                    ];
                    $createZoneResults = localAPI('dnsmanager', $postfields);
                    logModuleCall(
                        'RealtimeRegisterSsl [dns]', 'createZone',
                        print_r($postfields, true),
                        print_r($createZoneResults, true)
                    );
                }

                $zone = Capsule::table('dns_manager2_zone')->where('name', $zoneDomain)->first();
                if (isset($zone->id) && !empty($zone->id)) {
                    $postfields = [
                        'dnsaction' => 'createRecords',
                        'zone_id' => $zone->id,
                        'records' => $records
                    ];
                    $createRecordCnameResults = localAPI('dnsmanager', $postfields);
                    logModuleCall(
                        'RealtimeRegisterSsl [dns]',
                        'updateZone',
                        print_r($postfields, true),
                        print_r($createRecordCnameResults, true)
                    );
                }
            }
            if (isset($orderDetails['san']) && !empty($orderDetails['san'])) {
                foreach ($orderDetails['san'] as $sanrecord) {
                    $records = [];
                    if (
                        isset($sanrecord['validation']['dns']['record'])
                        && !empty($sanrecord['validation']['dns']['record'])
                    ) {
                        if (file_exists($loaderDNS)) {
                            $helper = new DomainHelper(str_replace('*.', '', $sanrecord['san_name']));
                            $zoneDomain = $helper->getDomainWithTLD();
                        }

                        if (strpos($sanrecord['validation']['dns']['record'], 'CNAME') !== false) {
                            $dnsrecord = explode("CNAME", $sanrecord['validation']['dns']['record']);
                            $records[] = [
                                'name' => trim(rtrim($dnsrecord[0])) . '.',
                                'type' => 'CNAME',
                                'ttl' => '3600',
                                'data' => trim(rtrim($dnsrecord[1]))
                            ];
                        } else {
                            $dnsrecord = explode("IN   TXT", $sanrecord['validation']['dns']['record']);
                            $length = strlen(trim(rtrim($dnsrecord[1])));
                            $records[] = [
                                'name' => trim(rtrim($dnsrecord[0])) . '.',
                                'type' => 'TXT',
                                'ttl' => '14440',
                                'data' => substr(trim(rtrim($dnsrecord[1])), 1, $length - 2)
                            ];
                        }

                        $zone = Capsule::table('dns_manager2_zone')->where('name', $zoneDomain)->first();
                        if (!isset($zone->id) || empty($zone->id)) {
                            $postfields = [
                                'action' => 'dnsmanager',
                                'dnsaction' => 'createZone',
                                'zone_name' => $zoneDomain,
                                'type' => '2',
                                'relid' => $this->p['serviceid'],
                                'zone_ip' => '',
                                'userid' => $this->p['userid']
                            ];
                            $createZoneResults = localAPI('dnsmanager', $postfields);
                            logModuleCall(
                                'RealtimeRegisterSsl [dns]',
                                'createZone',
                                print_r($postfields, true),
                                print_r($createZoneResults, true)
                            );
                        }

                        $zone = Capsule::table('dns_manager2_zone')->where('name', $zoneDomain)->first();
                        if (isset($zone->id) && !empty($zone->id)) {
                            $postfields = [
                                'dnsaction' => 'createRecords',
                                'zone_id' => $zone->id,
                                'records' => $records
                            ];
                            $createRecordCnameResults = localAPI('dnsmanager', $postfields);
                            logModuleCall(
                                'RealtimeRegisterSsl [dns]',
                                'updateZone',
                                print_r($postfields, true),
                                print_r($createRecordCnameResults, true)
                            );
                        }
                    }
                }
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
        foreach ($this->post['sansDomansDcvMethod'] as $newMethod) {
            $data[] = $newMethod;
        }
        return $data;
    }

    private function validateWebServer()
    {
        if ($this->post['webservertype'] == 0) {
            throw new Exception(Lang::getInstance()->T('mustSelectServer'));
        }
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

        $invalidDomains = Domains::getInvalidDomains(
            $sansDomains,
            in_array($apiProductId, self::PRODUCTS_WITH_ADDITIONAL_SAN_VALIDATION)
        );

        if ($apiProductId != '144') {
            if (count($invalidDomains)) {
                throw new Exception(Lang::getInstance()->T('incorrectSans') . implode(', ', $invalidDomains));
            }
        } else {
            if (count($invalidDomains)) {
                $iperror = false;

                foreach ($invalidDomains as $domainname) {
                    if (!filter_var($domainname, FILTER_VALIDATE_IP)) {
                        $iperror = true;
                    }
                }

                if ($iperror) {
                    throw new Exception('SANs are incorrect');
                }
            }
        }

        $includedSans = $this->p[ConfigOptions::PRODUCT_INCLUDED_SANS];
        $boughtSans = $this->p['configoptions'][ConfigOptions::OPTION_SANS_COUNT];
        $sansLimit = $this->getSansLimit();
        if (count($sansDomains) > $sansLimit) {
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

        //$this->orderStatus = \MGModule\RealtimeRegisterSsl\eProviders\ApiProvider::getInstance()->getApi()->getOrderStatus($this->sslService->remoteid);

        if (!in_array($this->sslService->configdata->ssl_status, ['active', 'COMPLETED'])) {
            throw new Exception(Lang::getInstance()->T('notAllowToReissue'));
        }
    }

    private function loadServerList()
    {
        try {
            $apiRepo = new Products();
            $apiProduct = $apiRepo->getProduct(KeyToIdMapping::getIdByKey($this->p[ConfigOptions::API_PRODUCT_ID]));

            if ($apiProduct->brand == 'comodo') {
                $apiWebServers = [
                    ['id' => '35', 'software' => 'IIS'],
                    ['id' => '-1', 'software' => 'Any Other']
                ];
            } else {
                $apiWebServers = [
                    ['id' => '18', 'software' => 'IIS'],
                    ['id' => '18', 'software' => 'Any Other']
                ];
            }

            $this->vars['webServers'] = $apiWebServers;
            FlashService::set('realtimeregister_ssl_SERVER_LIST_' . ConfigOptions::API_PRODUCT_ID, $apiWebServers);
        } catch (Exception $ex) {
            $this->vars['errors'][] .= Lang::getInstance()->T('canNotFetchWebServer');
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
