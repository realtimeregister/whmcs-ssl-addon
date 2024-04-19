<?php

namespace MGModule\RealtimeRegisterSsl\eServices\provisioning;

use Exception;
use MGModule\DNSManager2\addon;
use MGModule\DNSManager2\loader;
use MGModule\DNSManager2\mgLibs\custom\helpers\DomainHelper;
use MGModule\RealtimeRegisterSsl\eHelpers\Cpanel;
use MGModule\RealtimeRegisterSsl\eHelpers\Invoice;
use MGModule\RealtimeRegisterSsl\eHelpers\SansDomains;
use MGModule\RealtimeRegisterSsl\eProviders\ApiProvider;
use MGModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\Products;
use MGModule\RealtimeRegisterSsl\eRepository\whmcs\service\SSL;
use MGModule\RealtimeRegisterSsl\eServices\FlashService;
use MGModule\RealtimeRegisterSsl\models\whmcs\service\Service as Service;
use SandwaveIo\RealtimeRegister\Api\CertificatesApi;
use SandwaveIo\RealtimeRegister\Api\ProcessesApi;
use SandwaveIo\RealtimeRegister\Domain\Product;
use stdClass;
use WHMCS\Database\Capsule;
use MGModule\RealtimeRegisterSsl\models\orders\Repository as OrderRepo;
use MGModule\RealtimeRegisterSsl\models\logs\Repository as LogsRepo;

class SSLStepThree {

    /**
     *
     * @var array
     */
    private $p;

    /**
     *
     * @var \MGModule\RealtimeRegisterSsl\eModels\whmcs\service\SSL
     */
    private $sslConfig;

    private $invoiceGenerator;

    /**
     *
     * @var \MGModule\RealtimeRegisterSsl\eModels\RealtimeRegisterSsl\Product
     */
    private $apiProduct;

    public function __construct(&$params) {
        $this->p = &$params;
        if(!isset($this->p['model'])) {
            $this->p['model'] = \WHMCS\Service\Service::find($this->p['serviceid']);
        }

        $this->invoiceGenerator = new Invoice();
    }

    public function run() {
        try {
            SansDomains::decodeSanAprroverEmailsAndMethods($_POST);
            $this->setMainDomainDcvMethod($_POST);
            $this->setSansDomainsDcvMethod($_POST);
            $this->SSLStepThree();
        } catch (Exception $ex) {
            $this->redirectToStepOne($ex->getMessage());
        }
    }

    private function setMainDomainDcvMethod($post): void
    {
        $this->p['fields']['dcv_method']  = $post['dcvmethodMainDomain'];
    }

    private function setSansDomainsDcvMethod($post): void
    {
        if(isset($post['dcvmethod']) && is_array($post['dcvmethod'])) {
            $this->p['sansDomansDcvMethod'] = $post['dcvmethod'];
        }
    }

    private function SSLStepThree() {

        $this->loadSslConfig();
        $this->loadApiProduct();
        $this->orderCertificate();
    }

    private function loadSslConfig() {
        $repo = new SSL();
        $this->sslConfig  = $repo->getByServiceId($this->p['serviceid']);
        if (is_null($this->sslConfig)) {
            throw new Exception('Record for ssl service not exist.');
        }
    }

    private function loadApiProduct() {
        $apiProductId     = $this->p[ConfigOptions::API_PRODUCT_ID];

        $apiRepo          = new Products();
        $this->apiProduct = $apiRepo->getProduct($apiProductId);
    }

    private function orderCertificate()
    {
        echo '<pre>';
        if(isset($_POST['approveremail']) && $_POST['approveremail'] == 'defaultemail@defaultemail.com')
        {
            unset($_POST['approveremail']);
        }

        $billingPeriods = [
            'Free Account'  =>  $this->p[ConfigOptions::API_PRODUCT_MONTHS],
            'One Time'      =>  $this->p[ConfigOptions::API_PRODUCT_MONTHS],
            'Monthly'       =>  12,
            'Quarterly'     =>  3,
            'Semi-Annually' =>  6,
            'Annually'      =>  12,
            'Biennially'    =>  24,
            'Triennially'   =>  36,
        ];

        if(!empty($this->p[ConfigOptions::API_PRODUCT_ID])) {
            $apiRepo       = new Products();
            $apiProduct    = $apiRepo->getProduct($this->p[ConfigOptions::API_PRODUCT_ID]);

            //get available periods for product
            $productAvailablePeriods = $apiProduct->getPeriods();
            //if certificate have monthly billing cycle available
            if(in_array('1', $productAvailablePeriods)) {
                $billingPeriods['Monthly'] = 1;
            } else {
                if(!in_array('12', $productAvailablePeriods)) {
                    $billingPeriods['Monthly'] = $productAvailablePeriods[0];
                }
            }

            //one time billing set period to 12 months if avaiable else leave max period
            if(in_array('12', $productAvailablePeriods)) {
                $billingPeriods['One Time'] = 12;
            }
        }

        if($this->p[ConfigOptions::MONTH_ONE_TIME] && !empty($this->p[ConfigOptions::MONTH_ONE_TIME]))
        {
            $billingPeriods['One Time'] = $this->p[ConfigOptions::MONTH_ONE_TIME];
        }

        $order = [];

        $apiRepo = new Products();
        $productDetails = $apiRepo->getProduct($order['product_id']);

        $order['product'] = $apiProduct->product;
        $order['period'] = $billingPeriods[$this->p['model']->billingcycle];
        $order['csr'] = str_replace('\n', "\n", $this->p['csr']); // Fix for RT-14675
        /** @var Product $productDetails */
        $productDetails = ApiProvider::getInstance()->getApi(CertificatesApi::class)
            ->getProduct($productDetails->product);

        $mapping = [
            'organization' => 'orgname',
            'country' => 'country',
            'state' => 'state',
            'address' => 'address1',
            'postalCode' => 'postcode',
            'city' => 'city',
            'saEmail' => 'email',
            'dcv' => 'dcv'
        ]; // 'coc','language', 'uniqueValue','authKey' == missing

        foreach ($productDetails->requiredFields as $value) {
            if ($value === 'approver') {
                $order['approver'] = [
                    'firstName' => $this->p['firstname'],
                    'lastName' => $this->p['lastname'],
                    'email' => $this->p['email'],
                    'voice' => $this->p['phonenumber']
                ];
            } else {
                $order[$value] = $this->p[$mapping[$value]];
            }
        }

        $sanEnabledForWHMCSProduct = $this->p[ConfigOptions::PRODUCT_ENABLE_SAN] === 'on';

        $san_domains = explode(PHP_EOL, $this->p['configdata']['fields']['sans_domains']);
        $wildcard_domains = explode(PHP_EOL, $this->p['configdata']['fields']['wildcard_san']);
        $all_san = array_merge($san_domains, $wildcard_domains);

        $decodedCSR = [];
        $decodedCSR['csrResult']['CN'] = $this->p['domain'];

        if ($sanEnabledForWHMCSProduct && count($all_san)) {
            $sansDomains = $this->p['configdata']['fields']['sans_domains'];
            $sansDomains = SansDomains::parseDomains($sansDomains);

            $sansDomainsWildcard = $this->p['configdata']['fields']['wildcard_san'];
            $sansDomainsWildcard = SansDomains::parseDomains($sansDomainsWildcard);

            $sansDomains = array_merge($sansDomains, $sansDomainsWildcard);

            foreach ($sansDomains as $sansDomain) {
                $order['san'][] = $sansDomain;
            }
            //if entered san is the same as main domain
            if (is_array($_POST['approveremails'])) {
                if (count($sansDomains) != count($_POST['approveremails'])) {
                    foreach ($sansDomains as $key => $domain) {
                        if ($decodedCSR['csrResult']['CN'] == $domain) {
                            unset($sansDomains[$key]);
                        }
                    }
                }
            }

            if (!empty($sanDcvMethods = $this->getSansDomainsValidationMethods())) {
                $i = 0;
                foreach ($_POST['approveremails'] as $approverDomain => $approveremail) {
                    if ($sanDcvMethods[$i] != 'EMAIL') {
                        $order['dcv'][] = ['commonName' => $approverDomain, 'type' => strtoupper($sanDcvMethods[$i])];
                    } else {
                        $order['dcv'][] = ['commonName' => $approverDomain, 'type' => 'EMAIL', 'email' => $approveremail];
                    }
                    $i++;
                }
            }

            $csrDecode = ApiProvider::getInstance()->getApi(CertificatesApi::class)->decodeCsr($order['csr']);

            if ($_POST['dcvmethodMainDomain'] === 'EMAIL') {
                $order['dcv'][] = [
                    'commonName' => $csrDecode['commonName'],
                    'type' => $_POST['dcvmethodMainDomain'],
                    'email' => $_POST['approveremail']
                ];
            } else {
                $order['dcv'][] = ['commonName' => $csrDecode['commonName'], 'type' => $_POST['dcvmethodMainDomain']];
            }
            $apiRepo = new Products();
            $apiProduct = $apiRepo->getProduct($order['product_id']);
        }

        $orderType = $this->p['fields']['order_type'];
        switch ($orderType)
        {
            case 'renew':
                $addedSSLOrder = ApiProvider::getInstance()->getApi(CertificatesApi::class)->addSSLRenewOrder($order);
                break;
            case 'new':
            default:
                $addedSSLOrder = ApiProvider::getInstance()->getApi(CertificatesApi::class)->requestCertificate(
                    ApiProvider::getCustomer(),
                    $order['product'],
                    $order['period'],
                    $order['csr'],
                    $order['san'],
                    $order['organization'],
                    null,
                    $order['address'],
                    $order['postalCode'],
                    $order['city'],
                    null,
                    $order['approver']['email'],
                    $order['approver'],
                    $order['country'],
                    null,
                    $order['dcv'],
                    null,
                    null,
                    null,
                );
                break;
        }

        /** @var ProcessesApi $processesApi */
        $processesApi = ApiProvider::getInstance()->getApi(ProcessesApi::class);
        $addedSSLOrderInformation = $processesApi->get($addedSSLOrder);

        //update domain column in tblhostings
        $service = new Service($this->p['serviceid']);
        $service->save(['domain' => $decodedCSR['csrResult']['CN']]);

        // dns manager
        sleep(2);
        $dnsmanagerfile = dirname(dirname(dirname(dirname(dirname(__DIR__))))) . DIRECTORY_SEPARATOR . 'includes'
            . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'dnsmanager.php';
        $checkTable = Capsule::schema()->hasTable('dns_manager2_zone');
        if (file_exists($dnsmanagerfile) && $checkTable !== false)
        {
            $zoneDomain = $decodedCSR['csrResult']['CN'];
            $loaderDNS = dirname(dirname(dirname(dirname(dirname(__DIR__))))) . DIRECTORY_SEPARATOR . 'modules'
                . DIRECTORY_SEPARATOR . 'addons' .DIRECTORY_SEPARATOR.'DNSManager2'.DIRECTORY_SEPARATOR.'loader.php';
            if (file_exists($loaderDNS)) {
                require_once $loaderDNS;
                $loader = new loader();
                addon::I(true);
                $helper = new DomainHelper($decodedCSR['csrResult']['CN']);
                $zoneDomain = $helper->getDomainWithTLD();
            }

            $records = [];
            if (
                isset($addedSSLOrder['approver_method']['dns']['record'])
                && !empty($addedSSLOrder['approver_method']['dns']['record'])
            ) {
                if (strpos($addedSSLOrder['approver_method']['dns']['record'], 'CNAME') !== false)
                {
                    $dnsrecord = explode("CNAME", $addedSSLOrder['approver_method']['dns']['record']);
                    $records[] = array(
                        'name' => trim(rtrim($dnsrecord[0])).'.',
                        'type' => 'CNAME',
                        'ttl' => '3600',
                        'data' => trim(rtrim($dnsrecord[1]))
                    );
                }
                else
                {
                    $dnsrecord = explode("IN   TXT", $addedSSLOrder['approver_method']['dns']['record']);
                    $length = strlen(trim(rtrim($dnsrecord[1])));
                    $records[] = array(
                        'name' => trim(rtrim($dnsrecord[0])).'.',
                        'type' => 'TXT',
                        'ttl' => '14440',
                        'data' => substr(trim(rtrim($dnsrecord[1])),1, $length-2)
                    );
                }
                $zone = Capsule::table('dns_manager2_zone')->where('name', $zoneDomain)->first();
                if (!isset($zone->id) || empty($zone->id))
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
                    logModuleCall(
                        'RealtimeRegisterSsl [dns]',
                        'createZone',
                        print_r($postfields, true),
                        print_r($createZoneResults, true)
                    );
                }

                $zone = Capsule::table('dns_manager2_zone')->where('name', $zoneDomain)->first();
                if(isset($zone->id) && !empty($zone->id))
                {
                    $postfields =  array(
                        'dnsaction' => 'createRecords',
                        'zone_id' => $zone->id,
                        'records' => $records);
                    $createRecordCnameResults = localAPI('dnsmanager' ,$postfields);
                    logModuleCall(
                        'RealtimeRegisterSsl [dns]',
                        'updateZone',
                        print_r($postfields, true),
                        print_r($createRecordCnameResults, true)
                    );
                }

            }
            if (isset($addedSSLOrder['san']) && !empty($addedSSLOrder['san']))
            {
                foreach($addedSSLOrder['san'] as $sanrecord)
                {
                    $records = [];
                    if (
                        isset($sanrecord['validation']['dns']['record'])
                        && !empty($sanrecord['validation']['dns']['record'])
                    ) {
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
                        } else {
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
                        if (!isset($zone->id) || empty($zone->id))
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
                            logModuleCall(
                                'RealtimeRegisterSsl [dns]',
                                'createZone',
                                print_r($postfields, true),
                                print_r($createZoneResults, true)
                            );
                        }

                        $zone = Capsule::table('dns_manager2_zone')->where('name', $zoneDomain)->first();
                        if (isset($zone->id) && !empty($zone->id))
                        {
                            $postfields =  array(
                                'dnsaction' => 'createRecords',
                                'zone_id' => $zone->id,
                                'records' => $records);
                            $createRecordCnameResults = localAPI('dnsmanager' ,$postfields);
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
        /** @var ProcessesApi $processesApi */
        $processesApi = ApiProvider::getInstance()->getApi(ProcessesApi::class);
        $orderDetails = $processesApi->get($addedSSLOrder['order_id']);

        if($this->p[ConfigOptions::MONTH_ONE_TIME] && !empty($this->p[ConfigOptions::MONTH_ONE_TIME]))
        {
            $service = new Service($this->p['serviceid']);
            $service->save(array('termination_date' => $orderDetails['valid_till']));
        }

        $this->sslConfig->setRemoteId($addedSSLOrder['order_id']);
        $this->sslConfig->setApproverEmails($order['approver_emails']);
       
        $this->sslConfig->setCa($orderDetails['ca_code']);
        $this->sslConfig->setCrt($orderDetails['crt_code']);
        $this->sslConfig->setPartnerOrderId($orderDetails['partner_order_id']);
        $this->sslConfig->setValidFrom($orderDetails['valid_from']);
        $this->sslConfig->setValidTill($orderDetails['valid_till']);
        $this->sslConfig->setDomain($orderDetails['domain']);
        $this->sslConfig->setOrderStatusDescription($orderDetails['status_description']);
        $this->sslConfig->setApproverMethod($orderDetails['approver_method']);
        $this->sslConfig->setDcvMethod($orderDetails['dcv_method']);
        $this->sslConfig->setProductId($orderDetails['product_id']);
        $this->sslConfig->setSanDetails($orderDetails['san']);
        $this->sslConfig->setSSLStatus($orderDetails['status']);
        $this->sslConfig->save();
        //try to mark previous order as completed if it is autoinvoiced and autocreated product
        $this->invoiceGenerator->markPreviousOrderAsCompleted($this->p['serviceid']);

        FlashService::set('REALTIMEREGISTERSSL_WHMCS_SERVICE_TO_ACTIVE', $this->p['serviceid']);
        Invoice::insertDomainInfoIntoInvoiceItemDescription($this->p['serviceid'], $decodedCSR['csrResult']['CN']);

        $sslOrder = Capsule::table('tblsslorders')->where('serviceid', $this->p['serviceid'])->first();
        $orderRepo = new OrderRepo();
        $orderRepo->addOrder(
            $this->p['userid'],
            $this->p['serviceid'],
            $sslOrder->id,
            $orderDetails['dcv_method'],
            'Pending Verification',
            $addedSSLOrder
        );

        $logs = new LogsRepo();
        $logs-> addLog($this->p['userid'], $this->p['serviceid'], 'success', 'The order has been placed.');

        $order = Capsule::table('REALTIMEREGISTERSSL_orders')->where('service_id', $this->p['serviceid'])->first();
        $sslOrder = Capsule::table('tblsslorders')->where('id', $order->ssl_order_id)->first();
        $service = Capsule::table('tblhosting')->where('id', $this->p['serviceid'])->first();
        $orderDetails = json_decode($order->data, true);


        $revalidate = false;

        foreach ($orderDetails['approver_method'] as $method => $data)
        {
            try {
                $cPanelService = new \MGModule\RealtimeRegisterSsl\eModels\cpanelservices\Service();
                $cpanelDetails = $cPanelService->getServiceByDomain($service->userid, $service->domain);
                $cpanel = new Cpanel();

                if ($cpanelDetails === false) {
                    continue;
                }

                if ($method == 'http' || $method == 'https') {
                    $cpanel->setService($cpanelDetails);
                    $directory = $cpanel->getRootDirectory($cpanelDetails->user, $service->domain);
                    $content = $data['content'];

                    $cpanel->addDirectory($cpanelDetails->user, [
                        [
                            'dir' => $directory,
                            'name' => '.well-known',
                        ],
                        [
                            'dir' => $directory . '/.well-known',
                            'name' => 'pki-validation',
                        ]
                    ]);

                    $cpanel->saveFile($cpanelDetails->user, $data['filename'], $directory . '/.well-known/pki-validation/', $content);
                    $logs-> addLog($this->p['userid'], $this->p['serviceid'], 'success', 'The '.$service->domain.' domain has been verified using the file method.');
                    $revalidate = true;
                }

                if ($method == 'dns') {

                    if (strpos($data['record'], 'CNAME') !== false) {
                        $cpanel->setService($cpanelDetails);
                        $records = explode('CNAME', $data['record']);
                        $record = new stdClass();
                        $record->domain = $service->domain;
                        $record->name = trim($records[0]).'.';
                        $record->cname = trim($records[1]);
                        $record->type = 'CNAME';
                        $cpanel->addRecord($cpanelDetails->user, $record);
                        $logs->addLog($this->p['userid'], $this->p['serviceid'], 'success', 'The ' . $service->domain . ' domain has been verified using the dns method.');
                        $revalidate = true;
                    }

                    if (strpos($data['record'], 'IN   TXT') !== false) {
                        $cpanel->setService($cpanelDetails);
                        $records = explode('IN   TXT', $data['record']);
                        $record = new stdClass();
                        $record->domain = $service->domain;
                        $record->name = trim($records[0]);
                        $record->type = 'TXT';
                        $record->ttl = "14400";
                        $record->txtdata = str_replace('"','', trim($records[1]));
                        $cpanel->addRecord($cpanelDetails->user, $record);
                        $logs->addLog($this->p['userid'], $this->p['serviceid'], 'success', 'The ' . $service->domain . ' domain has been verified using the dns method.');
                        $revalidate = true;
                    }

                }

            } catch (Exception $e) {
                $logs-> addLog($this->p['userid'], $this->p['serviceid'], 'error', '['.$service->domain.'] Error:'.$e->getMessage());
                continue;
            }
        }

        if(isset($orderDetails['san']) && !empty($orderDetails['san']))
        {
            foreach ($orderDetails['san'] as $san)
            {
                try {

                    $cPanelService = new \MGModule\RealtimeRegisterSsl\eModels\cpanelservices\Service();
                    $cpanelDetails = $cPanelService->getServiceByDomain($service->userid, $san['san_name']);

                    if($cpanelDetails === false) continue;

                    $cpanel = new Cpanel();
                    $cpanel->setService($cpanelDetails);

                    if($san['validation_method'] == 'http' || $san['validation_method'] == 'https')
                    {
                        $directory = $cpanel->getRootDirectory($cpanelDetails->user, $san['san_name']);
                        $content = $san['validation'][$san['validation_method']]['content'];

                        $cpanel->addDirectory($cpanelDetails->user, [
                            [
                                'dir' => $directory,
                                'name' => '.well-known',
                            ],
                            [
                                'dir' => $directory . '/.well-known',
                                'name' => 'pki-validation',
                            ]
                        ]);

                        $cpanel->saveFile($cpanelDetails->user, $san['validation'][$san['validation_method']]['filename'], $directory.'/.well-known/pki-validation/', $content);
                        $logs-> addLog($this->p['userid'], $this->p['serviceid'], 'success', 'The '.$san['san_name'].' domain has been verified using the file method.');
                        $revalidate = true;
                    }

                    if($san['validation_method'] == 'dns')
                    {

                        if (strpos($san['validation'][$san['validation_method']]['record'], 'CNAME') !== false) {
                            $records = explode('CNAME', $san['validation'][$san['validation_method']]['record']);
                            $record = new stdClass();
                            $record->domain = $san['san_name'];
                            $record->name = trim($records[0]).'.';
                            $record->cname = trim($records[1]);
                            $record->type = 'CNAME';
                            $cpanel->addRecord($cpanelDetails->user, $record);
                            $logs->addLog($this->p['userid'], $this->p['serviceid'], 'success', 'The ' . $san['san_name'] . ' domain has been verified using the dns method.');
                            $revalidate = true;
                        }

                        if (strpos($san['validation'][$san['validation_method']]['record'], 'IN   TXT') !== false) {
                            $records = explode('IN   TXT', $san['validation'][$san['validation_method']]['record']);
                            $record = new stdClass();
                            $record->domain = $san['san_name'];
                            $record->name = trim($records[0]);
                            $record->type = 'TXT';
                            $record->ttl = "14400";
                            $record->txtdata = str_replace('"','', trim($records[1]));
                            $cpanel->addRecord($cpanelDetails->user, $record);
                            $logs->addLog($this->p['userid'], $this->p['serviceid'], 'success', 'The ' . $san['san_name'] . ' domain has been verified using the dns method.');
                            $revalidate = true;
                        }

                    }

                } catch (Exception $e) {

                    $logs-> addLog($this->p['userid'], $this->p['serviceid'], 'error', '['.$san['san_name'].'] Error:'.$e->getMessage());
                    continue;
                }
            }
        }

        if($revalidate === true) {
            try {

                $dataAPI = [
                    'domain' => $service->domain
                ];
                $response = ApiProvider::getInstance()->getApi()->revalidate($sslOrder->remoteid, $dataAPI);

                $logs->addLog($this->p['userid'], $this->p['serviceid'], 'info', '[' . $service->domain . '] Revalidate,');

                if (isset($response['success']) && !empty($response['success'])) {
                    $orderRepo->updateStatus($this->p['serviceid'], 'Pending Installation');
                    $logs->addLog($this->p['userid'], $this->p['serviceid'], 'success', '[' . $service->domain . '] Revalidate Succces.');
                }

            } catch (Exception $e) {
                $logs->addLog($this->p['userid'], $this->p['serviceid'], 'error', '[' . $service->domain . '] Error:' . $e->getMessage());
            }
        }
}

    private function getSansDomainsValidationMethods() {
        $data = [];
        foreach ($this->p['sansDomansDcvMethod'] as  $newMethod) {
            $data[] = $newMethod;
        }
        return $data;
    }

    private function redirectToStepOne($error) {
        $_SESSION['REALTIMEREGISTERSSL_FLASH_ERROR_STEP_ONE'] = $error;
        header('Location: configuressl.php?cert='. $_GET['cert']);
        die();
    }

    /**
     * @param string $name
     * @param int $numberOfMonths
     * @return string
     */
    private function createProductName(string $name, int $numberOfMonths)
    {
        if ($numberOfMonths > 12) {
            $postFix = '_' . ($numberOfMonths/12) . 'years';
        }
        return $name . $postFix;
    }
}
