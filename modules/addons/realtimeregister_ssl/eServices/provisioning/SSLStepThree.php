<?php

namespace MGModule\RealtimeRegisterSsl\eServices\provisioning;

use Exception;
use MGModule\RealtimeRegisterSsl\eHelpers\Invoice;
use MGModule\RealtimeRegisterSsl\eHelpers\SansDomains;
use MGModule\RealtimeRegisterSsl\eProviders\ApiProvider;
use MGModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\KeyToIdMapping;
use MGModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\Products;
use MGModule\RealtimeRegisterSsl\eRepository\whmcs\service\SSL;
use MGModule\RealtimeRegisterSsl\eServices\FlashService;
use MGModule\RealtimeRegisterSsl\eServices\ManagementPanel\Dns\DnsControl;
use MGModule\RealtimeRegisterSsl\eServices\ManagementPanel\File\FileControl;
use MGModule\RealtimeRegisterSsl\models\logs\Repository as LogsRepo;
use MGModule\RealtimeRegisterSsl\models\orders\Repository as OrderRepo;
use MGModule\RealtimeRegisterSsl\models\whmcs\service\Service as Service;
use SandwaveIo\RealtimeRegister\Api\CertificatesApi;
use SandwaveIo\RealtimeRegister\Api\ProcessesApi;
use SandwaveIo\RealtimeRegister\Domain\CertificateInfoProcess;
use SandwaveIo\RealtimeRegister\Domain\Product;
use WHMCS\Database\Capsule;

class SSLStepThree
{
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

    public function __construct(&$params)
    {
        $this->p = &$params;
        if (!isset($this->p['model'])) {
            $this->p['model'] = \WHMCS\Service\Service::find($this->p['serviceid']);
        }

        $this->invoiceGenerator = new Invoice();
    }

    public function run()
    {
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
        $this->p['fields']['dcv_method'] = $post['dcvmethodMainDomain'];
    }

    private function setSansDomainsDcvMethod($post): void
    {
        if (isset($post['dcvmethod']) && is_array($post['dcvmethod'])) {
            $this->p['sansDomainsDcvMethod'] = $post['dcvmethod'];
        }
    }

    private function SSLStepThree()
    {
        $this->loadSslConfig();
        $this->loadApiProduct();
        $this->orderCertificate();
    }

    private function loadSslConfig()
    {
        $repo = new SSL();
        $this->sslConfig = $repo->getByServiceId($this->p['serviceid']);
        if (is_null($this->sslConfig)) {
            throw new Exception('Record for ssl service not exist.');
        }
    }

    private function loadApiProduct()
    {
        $apiProductId = $this->p[ConfigOptions::API_PRODUCT_ID];

        $apiRepo = new Products();
        $this->apiProduct = $apiRepo->getProduct(KeyToIdMapping::getIdByKey($apiProductId));
    }

    private function orderCertificate()
    {
        if (isset($_POST['approveremail']) && $_POST['approveremail'] == 'defaultemail@defaultemail.com') {
            unset($_POST['approveremail']);
        }

        if (!empty($this->p[ConfigOptions::API_PRODUCT_ID])) {
            $apiRepo = new Products();
            $apiProduct = $apiRepo->getProduct(KeyToIdMapping::getIdByKey($this->p[ConfigOptions::API_PRODUCT_ID]));
        }

        $order = [];

        $order['product'] = $apiProduct->product;
        $order['period'] = intval($this->p['configoptions']['years'][0]) * 12;

        $order['csr'] = str_replace('\n', "\n", $this->p['csr']); // Fix for RT-14675
        /** @var Product $productDetails */
        $productDetails = ApiProvider::getInstance()->getApi(CertificatesApi::class)
            ->getProduct($apiProduct->product);

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
                    'jobTitle' => $this->p['jobtitle'],
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

        $csrDecode = ApiProvider::getInstance()->getApi(CertificatesApi::class)->decodeCsr($order['csr']);

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
                        $order['dcv'][] = [
                            'commonName' => $approverDomain,
                            'type' => (strtoupper($sanDcvMethods[$i]) === 'HTTP' ? 'FILE' : strtoupper($sanDcvMethods[$i]))

                    ];
                    } else {
                        $order['dcv'][] =
                            ['commonName' => $approverDomain, 'type' => 'EMAIL', 'email' => $approveremail];
                    }
                    $i++;
                }
            }

        }

        if ($_POST['dcvmethodMainDomain'] === 'EMAIL') {
            $order['dcv'][] = [
                'commonName' => $csrDecode['commonName'],
                'type' => $_POST['dcvmethodMainDomain'],
                'email' => $_POST['approveremail']
            ];
        } else {
            $order['dcv'][] = [
                'commonName' => $csrDecode['commonName'],
                'type' => (strtoupper($_POST['dcvmethodMainDomain']) === 'HTTP' ? 'FILE' : strtoupper($_POST['dcvmethodMainDomain']))
            ];
        }

        $orderType = $this->p['fields']['order_type'];

        if ($this->p['fields']['org_division'] !== '') {
            $order['department'] = $this->p['fields']['org_division'];
        }
        $logs = new LogsRepo();

        try {
            /** @var CertificateInfoProcess $addedSSLOrder */
            switch ($orderType) {
                case 'renew':
                    $addedSSLOrder = ApiProvider::getInstance()->getApi(CertificatesApi::class)->renewCertificate($order);
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
                        $order['department'],
                        $order['address'],
                        $order['postalCode'],
                        $order['city'],
                        null,
                        $order['approver']['email'],
                        $order['approver'],
                        $order['country'],
                        null,
                        $order['dcv'],
                        $order['domain'],
                        null,
                        $order['state'],
                    );
                    break;
            }
        } catch (\SandwaveIo\RealtimeRegister\Exceptions\BadRequestException $exception) {
            $logs->addLog(
                $this->p['userid'], $this->p['serviceid'],
                'error',
                '[' . $csrDecode['commonName'] . '] Error:' . $exception->getMessage()
            );
            $decodedMessage = json_decode(str_replace('Bad Request: ', '', $exception->getMessage()), true);
            switch ($decodedMessage['type']) {
                case 'ObjectExists':
                    $reason = 'The request already exists';
                    break;
                case 'ValidationError':
                    $reason = 'Validation error';
                    if (strpos($decodedMessage['message'], 'try a reissue instead')) {
                        $reason = 'Certificate already exists, try a reissue instead';
                    }
                    break;
                default:
                    $reason = 'Unknown';
            }
            $this->redirectToStepOne($reason);
        }

        //update domain column in tblhostings
        $service = new Service($this->p['serviceid']);
        $service->save(['domain' => $decodedCSR['commonName']]);

        /** @var ProcessesApi $processesApi */
        $processesApi = ApiProvider::getInstance()->getApi(ProcessesApi::class);
        $orderDetails = $processesApi->get($addedSSLOrder->processId);
        if ($this->p[ConfigOptions::MONTH_ONE_TIME] && !empty($this->p[ConfigOptions::MONTH_ONE_TIME])) {
            $service = new Service($this->p['serviceid']);
            $service->save();
        }

        $approveremails = [];
        foreach ($order['dcv'] as $d) {
            if ($d['type'] === 'EMAIL') {
                $approveremails[] = $d['email'];
            }
        }

        $this->sslConfig->setRemoteId($orderDetails->id); // processid request
        $this->sslConfig->setApproverEmails($approveremails);

        $this->sslConfig->setCrt('--placeholder--');
        $this->sslConfig->setConfigdataKey('private_key', $this->p['private_key']);
        $this->sslConfig->setCsr($this->p['configdata']['csr']);
        $this->sslConfig->setDomain($orderDetails->identifier);
        $this->sslConfig->setOrderStatusDescription($orderDetails->status);
        $this->sslConfig->setApproverMethod($this->p['approvalmethod']);
        $this->sslConfig->setDcvMethod($this->p['fields']['dcv_method'] == 'http'?'FILE':$this->p['fields']['dcv_method']);
        $this->sslConfig->setProductId($this->p['configoption1']);
        $this->sslConfig->setSSLStatus($orderDetails->status);

        // Gets overwritten by whmcs ioncube encoded stuff atm >:(
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
            $this->p['fields']['dcv_method'],
            'Pending Verification',
            array_merge((array) $this->sslConfig->configdata, $addedSSLOrder->toArray())
        );

        $logs->addLog($this->p['userid'], $this->p['serviceid'], 'success', 'The order has been placed.');

        $order = Capsule::table('REALTIMEREGISTERSSL_orders')->where('service_id', $this->p['serviceid'])->first();
        $service = Capsule::table('tblhosting')->where('id', $this->p['serviceid'])->first();
        $orderDetails = json_decode($order->data, true);

        foreach ($orderDetails['validations']['dcv'] as $data) {
            try {
                $panel = \MGModule\RealtimeRegisterSsl\eServices\ManagementPanel\Api\Panel\Panel::getPanelData($data['commonName']);

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
    }

    private function getSansDomainsValidationMethods()
    {
        $data = [];
        foreach ($this->p['sansDomainsDcvMethod'] as $newMethod) {
            $data[] = $newMethod;
        }
        return $data;
    }

    private function redirectToStepOne($error)
    {
        $_SESSION['realtimeregister_ssl_FLASH_ERROR_STEP_ONE'] = $error;
        header('Location: configuressl.php?cert=' . $_GET['cert']);
        die();
    }
}
