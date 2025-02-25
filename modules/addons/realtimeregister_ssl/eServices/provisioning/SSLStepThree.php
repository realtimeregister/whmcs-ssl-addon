<?php

namespace AddonModule\RealtimeRegisterSsl\eServices\provisioning;

use AddonModule\RealtimeRegisterSsl\eHelpers\Invoice;
use AddonModule\RealtimeRegisterSsl\eHelpers\SansDomains;
use AddonModule\RealtimeRegisterSsl\eProviders\ApiProvider;
use AddonModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\KeyToIdMapping;
use AddonModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\Products;
use AddonModule\RealtimeRegisterSsl\eRepository\whmcs\service\SSL;
use AddonModule\RealtimeRegisterSsl\eServices\EmailTemplateService;
use AddonModule\RealtimeRegisterSsl\eServices\FlashService;
use AddonModule\RealtimeRegisterSsl\models\logs\Repository as LogsRepo;
use AddonModule\RealtimeRegisterSsl\models\orders\Repository as OrderRepo;
use AddonModule\RealtimeRegisterSsl\models\whmcs\service\Service as Service;
use Exception;
use RealtimeRegister\Api\CertificatesApi;
use RealtimeRegister\Api\ProcessesApi;
use RealtimeRegister\Domain\Product;
use RealtimeRegister\Exceptions\BadRequestException;
use WHMCS\Database\Capsule;

class SSLStepThree
{
    use SSLUtils;
    /**
     *
     * @var array
     */
    private $p;

    /**
     *
     * @var \AddonModule\RealtimeRegisterSsl\eModels\whmcs\service\SSL
     */
    private $sslConfig;

    private $invoiceGenerator;

    /**
     *
     * @var \AddonModule\RealtimeRegisterSsl\eModels\RealtimeRegisterSsl\Product
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
        $order['period'] = $this->parsePeriod($this->p['model']->billingcycle);

        $order['csr'] = str_replace('\n', "\n", $this->p['csr']); // Fix for RT-14675
        /** @var Product $productDetails */
        $productDetails = ApiProvider::getInstance()->getApi(CertificatesApi::class)
            ->getProduct($apiProduct->product);
        $order = array_merge($order, $this->mapRequestFields($this->p, $productDetails));

        $sanEnabledForWHMCSProduct = $this->p[ConfigOptions::PRODUCT_ENABLE_SAN] === 'on';

        $san_domains = explode(PHP_EOL, $this->p['configdata']['fields']['sans_domains']);
        $wildcard_domains = explode(PHP_EOL, $this->p['configdata']['fields']['wildcard_san']);
        $all_san = array_merge($san_domains, $wildcard_domains);

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
                        if ($csrDecode['commonName'] == $domain) {
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

        if ($this->p['fields']['org_division'] !== '') {
            $order['department'] = $this->p['fields']['org_division'];
        }
        $logs = new LogsRepo();

        try {
            $addedSSLOrder = ApiProvider::getInstance()
                ->getApi(CertificatesApi::class)
                ->requestCertificate(
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
                    $order['coc'],
                    $order['approver']['email'],
                    $order['approver'],
                    $order['country'],
                    null,
                    $order['dcv'],
                    $order['domain'],
                    null,
                    $order['state'],
                );
        } catch (BadRequestException $exception) {
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
                    $reason = 'Validation error: ' . $decodedMessage['message'];
                    break;
                case 'ConstraintViolationException':
                    $violations = array_map(
                        fn($violation) => 'field: '. $violation['field'] . ', message: '. $violation['message'],
                        $decodedMessage['violations']
                    );
                    $reason = "Constraint Violation: <br/>" . implode("<br/>", $violations);
                    break;
                default:
                    $reason = $exception->getMessage();
            }
            $this->redirectToStepOne($reason);
        }

        //update domain column in tblhostings
        $service = new Service($this->p['serviceid']);
        $service->save(['domain' => $csrDecode['commonName']]);

        /** @var ProcessesApi $processesApi */
        $processesApi = ApiProvider::getInstance()->getApi(ProcessesApi::class);
        $orderDetails = $processesApi->get($addedSSLOrder->processId);

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
        $this->sslConfig->setCsr(trim($this->p['configdata']['csr']));
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
        Invoice::insertDomainInfoIntoInvoiceItemDescription($this->p['serviceid'], $csrDecode['commonName']);

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

        sendMessage(EmailTemplateService::VALIDATION_INFORMATION_TEMPLATE_ID, $this->p['serviceid'], [
            'domain' => $this->sslConfig->getDomain(),
            'sslConfig' => $this->sslConfig->toArray()
        ]);

        $logs->addLog($this->p['userid'], $this->p['serviceid'], 'success', 'The order has been placed.');
        $this->processDcvEntries($addedSSLOrder->validations?->dcv?->toArray() ?? []);
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
