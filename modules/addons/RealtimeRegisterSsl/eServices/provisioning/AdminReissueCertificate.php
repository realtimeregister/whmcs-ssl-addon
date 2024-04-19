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
use MGModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\Products;
use MGModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\WebServers;
use MGModule\RealtimeRegisterSsl\eRepository\whmcs\service\SSL;
use MGModule\RealtimeRegisterSsl\mgLibs\Lang;
use MGModule\RealtimeRegisterSsl\models\whmcs\product\Product;
use MGModule\RealtimeRegisterSsl\models\whmcs\service\Service;
use SandwaveIo\RealtimeRegister\Api\CertificatesApi;
use SandwaveIo\RealtimeRegister\Api\ProcessesApi;
use function ModuleBuildParams;

class AdminReissueCertificate extends Ajax {

    private $p;
    private $serviceParams;
    private $product;

    function __construct(&$params) {
        $this->p = &$params;

    }

    public function run() {
        try {
            return $this->miniControler();
        } catch (Exception $ex) {
            $this->response(false, $ex->getMessage());
        }
    }

    private function miniControler() {


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

    private function reissueCertificate() {
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

        if ($sanEnabledForWHMCSProduct AND count($_POST['approveremails'])) {
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
        sleep(2);
        /** @var ProcessesApi $processesApi */
        $processesApi = ApiProvider::getInstance()->getApi(ProcessesApi::class);
        $orderDetails = $processesApi->get($sslService->remoteid);

        $decodedCSR   = ApiProvider::getInstance()->getApi(CertificatesApi::class)->decodeCsr($this->p['csr']);

        // dns manager
        $dnsmanagerfile = dirname(dirname(dirname(dirname(dirname(__DIR__))))).DIRECTORY_SEPARATOR.'includes'.DIRECTORY_SEPARATOR.'api'.DIRECTORY_SEPARATOR.'dnsmanager.php';
        $checkTable = Capsule::schema()->hasTable('dns_manager2_zone');
        if(file_exists($dnsmanagerfile) && $checkTable !== false)
        {
            $zoneDomain = $decodedCSR['csrResult']['CN'];
            $loaderDNS = dirname(dirname(dirname(dirname(dirname(__DIR__))))) . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'addons' .DIRECTORY_SEPARATOR.'DNSManager2'.DIRECTORY_SEPARATOR.'loader.php';
            if(file_exists($loaderDNS)) {
                require_once $loaderDNS;
                $loader = new loader();
                addon::I(true);
                $helper = new DomainHelper($decodedCSR['csrResult']['CN']);
                $zoneDomain = $helper->getDomainWithTLD();
            }

            $records = [];
            if(isset($orderDetails['approver_method']['dns']['record']) && !empty($orderDetails['approver_method']['dns']['record']))
            {
                if (strpos($orderDetails['approver_method']['dns']['record'], 'CNAME') !== false)
                {
                    $dnsrecord = explode("CNAME", $orderDetails['approver_method']['dns']['record']);
                    $records[] = array(
                        'name' => trim(rtrim($dnsrecord[0])).'.',
                        'type' => 'CNAME',
                        'ttl' => '3600',
                        'data' => trim(rtrim($dnsrecord[1]))
                    );
                }
                else
                {
                    $dnsrecord = explode("IN   TXT", $orderDetails['approver_method']['dns']['record']);
                    $length = strlen(trim(rtrim($dnsrecord[1])));
                    $records[] = array(
                        'name' => trim(rtrim($dnsrecord[0])).'.',
                        'type' => 'TXT',
                        'ttl' => '14440',
                        'data' => substr(trim(rtrim($dnsrecord[1])),1, $length-2)
                    );
                }

                $zone = Capsule::table('dns_manager2_zone')->where('name', $zoneDomain)->first();
                if(!isset($zone->id) || empty($zone->id))
                {
                    $postfields = array(
                        'action' => 'dnsmanager',
                        'dnsaction' => 'createZone',
                        'zone_name' => $zoneDomain,
                        'type' => '2',
                        'relid' => $this->p['serviceid'],
                        'zone_ip' => '',
                        'userid' => $this->p['userid']
                    );
                    $createZoneResults = localAPI('dnsmanager' ,$postfields);
                    logModuleCall('RealtimeRegisterSsl [dns]', 'createZone', print_r($postfields, true), print_r($createZoneResults, true));
                }

                $zone = Capsule::table('dns_manager2_zone')->where('name', $zoneDomain)->first();
                if(isset($zone->id) && !empty($zone->id))
                {
                    $postfields =  array(
                        'dnsaction' => 'createRecords',
                        'zone_id' => $zone->id,
                        'records' => $records);
                    $createRecordCnameResults = localAPI('dnsmanager' ,$postfields);
                    logModuleCall('RealtimeRegisterSsl [dns]', 'updateZone', print_r($postfields, true), print_r($createRecordCnameResults, true));
                }

            }
            if(isset($orderDetails['san']) && !empty($orderDetails['san']))
            {
                foreach($orderDetails['san'] as $sanrecord)
                {
                    $records = [];
                    if(isset($sanrecord['validation']['dns']['record']) && !empty($sanrecord['validation']['dns']['record']))
                    {
                        if(file_exists($loaderDNS)) {
                            $helper = new DomainHelper(str_replace('*.', '',$sanrecord['san_name']));
                            $zoneDomain = $helper->getDomainWithTLD();
                        }

                        if (strpos($sanrecord['validation']['dns']['record'], 'CNAME') !== false)
                        {
                            $dnsrecord = explode("CNAME", $sanrecord['validation']['dns']['record']);
                            $records[] = array(
                                'name' => trim(rtrim($dnsrecord[0])).'.',
                                'type' => 'CNAME',
                                'ttl' => '3600',
                                'data' => trim(rtrim($dnsrecord[1]))
                            );
                        }
                        else
                        {
                            $dnsrecord = explode("IN   TXT", $sanrecord['validation']['dns']['record']);
                            $length = strlen(trim(rtrim($dnsrecord[1])));
                            $records[] = array(
                                'name' => trim(rtrim($dnsrecord[0])).'.',
                                'type' => 'TXT',
                                'ttl' => '14440',
                                'data' => substr(trim(rtrim($dnsrecord[1])),1, $length-2)
                            );
                        }

                        $zone = Capsule::table('dns_manager2_zone')->where('name', $zoneDomain)->first();
                        if(!isset($zone->id) || empty($zone->id))
                        {
                            $postfields = array(
                                'action' => 'dnsmanager',
                                'dnsaction' => 'createZone',
                                'zone_name' => $zoneDomain,
                                'type' => '2',
                                'relid' => $this->p['serviceid'],
                                'zone_ip' => '',
                                'userid' => $this->p['userid']
                            );
                            $createZoneResults = localAPI('dnsmanager' ,$postfields);
                            logModuleCall('RealtimeRegisterSsl [dns]', 'createZone', print_r($postfields, true), print_r($createZoneResults, true));
                        }

                        $zone = Capsule::table('dns_manager2_zone')->where('name', $zoneDomain)->first();
                        if(isset($zone->id) && !empty($zone->id))
                        {
                            $postfields =  array(
                                'dnsaction' => 'createRecords',
                                'zone_id' => $zone->id,
                                'records' => $records);
                            $createRecordCnameResults = localAPI('dnsmanager' ,$postfields);
                            logModuleCall('RealtimeRegisterSsl [dns]', 'updateZone', print_r($postfields, true), print_r($createRecordCnameResults, true));
                        }

                    }
                }
            }
        }

        $sslService->setConfigdataKey('servertype', $data['webserver_type']);
        $sslService->setConfigdataKey('csr', $data['csr']);
        $sslService->setConfigdataKey('approveremail', $data['approver_email']);
        $sslService->setApproverEmails($data['approver_emails']);
        $sslService->setSansDomains($data['dns_names']);
        $sslService->save();

        try
        {
            $decodedCSR   = ApiProvider::getInstance()->getApi(CertificatesApi::class)->decodeCsr($this->p['csr']);
            Invoice::insertDomainInfoIntoInvoiceItemDescription($this->p['serviceId'], $decodedCSR['csrResult']['CN'], true);

            $service = new Service($this->p['serviceId']);
            $service->save(array('domain' => $decodedCSR['csrResult']['CN']));

            $configDataUpdate = new UpdateConfigData($sslService);
            $configDataUpdate->run();
        }
        catch(Exception $e)
        {

        }

        $this->response(true, 'Certificate was successfully reissued.');

    }

    private function webServers() {
        $this->moduleBuildParams();
        $apiProductId  = $this->serviceParams[ConfigOptions::API_PRODUCT_ID];
        $apiRepo       = new Products();
        $apiProduct    = $apiRepo->getProduct($apiProductId);
        $apiWebServers = WebServers::getAll($apiProduct->getWebServerTypeId());
        $this->response(true, 'Web Servers', $apiWebServers);

    }

    private function moduleBuildParams() {
        $this->serviceParams = ModuleBuildParams($this->p['serviceId']);
        if (empty($this->serviceParams)) {
            throw new Exception('Can not build module params.');
        }
    }

    private function getApprovals() {
        $this->validateSanDomains();
        $this->validateSansDomainsWildcard();
        $this->validateServerType();
        $decodeCSR = ApiProvider::getInstance()->getApi(CertificatesApi::class)->decodeCsr($this->p['csr']);

        $service = new Service($this->p['serviceId']);
        $product = new Product($service->productID);

        if($product->configuration()->text_name != '144')
        {
            if(isset($decodeCSR['csrResult']['errorMessage'])){
                throw new Exception($decodeCSR['csrResult']['errorMessage']);
            }
        }
        $mainDomain   = $decodeCSR['csrResult']['CN'];
        $domains      = $mainDomain . PHP_EOL . $this->p['sanDomains'].PHP_EOL.$this->p['sanDomainsWildcard'];
        $parseDomains = SansDomains::parseDomains($domains);
        $SSLStepTwoJS = new SSLStepTwoJS($this->p);
        $this->response(true, 'Approve Emails', $SSLStepTwoJS->fetchApprovalEmailsForSansDomains($parseDomains));

    }

    private function validateSanDomains() {
        $this->moduleBuildParams();
        $sansDomains = $this->p['sanDomains'];
        $sansDomains = SansDomains::parseDomains($sansDomains);

        $apiProductId     = $this->serviceParams[ConfigOptions::API_PRODUCT_ID];

        $invalidDomains = Domains::getInvalidDomains($sansDomains, in_array($apiProductId, array(100, 99, 63)));

        if($apiProductId != '144') {

            if (count($invalidDomains)) {
                throw new Exception(Lang::getInstance()->T('incorrectSans') . implode(', ', $invalidDomains));
            }

        } else {

            if (count($invalidDomains)) {

                $iperror = false;

                foreach($invalidDomains as $domainname)
                {
                    if(!filter_var($domainname, FILTER_VALIDATE_IP)) {
                        $iperror = true;
                    }
                }

                if ($iperror) {
                    throw new Exception('SANs are incorrect');
                }
            }

        }

        $includedSans = $this->serviceParams[ConfigOptions::PRODUCT_INCLUDED_SANS];
        $boughtSans   = $this->serviceParams['configoptions'][ConfigOptions::OPTION_SANS_COUNT];
        $sansLimit    = $this->getSansLimit();
        if (count($sansDomains) > $sansLimit) {
            throw new Exception(Lang::getInstance()->T('exceededLimitOfSans'));
        }
    }

    private function validateSansDomainsWildcard() {
        $sansDomainsWildcard = $this->p['sanDomainsWildcard'];
        $sansDomainsWildcard = SansDomains::parseDomains($sansDomainsWildcard);

        foreach($sansDomainsWildcard as $domain)
        {
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

    private function validateServerType() {
        if($this->p['webServer'] == 0) {
            throw new Exception('You must select client server type');
        }
    }

    private function getSansLimit() {
        $sanEnabledForWHMCSProduct = $this->serviceParams[ConfigOptions::PRODUCT_ENABLE_SAN] === 'on';
        if (!$sanEnabledForWHMCSProduct) {
            return 0;
        }
        $includedSans = (int) $this->serviceParams[ConfigOptions::PRODUCT_INCLUDED_SANS];
        $boughtSans   = (int) $this->serviceParams['configoptions'][ConfigOptions::OPTION_SANS_COUNT];
        return $includedSans + $boughtSans;

    }

    private function getSansLimitWildcard() {
        $includedSans = (int) $this->serviceParams[ConfigOptions::PRODUCT_INCLUDED_SANS_WILDCARD];
        $boughtSans   = (int) $this->serviceParams['configoptions']['sans_wildcard_count'];
        return $includedSans + $boughtSans;

    }
}
