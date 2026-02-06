<?php

namespace AddonModule\RealtimeRegisterSsl\eServices\provisioning;

use AddonModule\RealtimeRegisterSsl\eHelpers\Invoice;
use AddonModule\RealtimeRegisterSsl\eHelpers\SansDomains;
use AddonModule\RealtimeRegisterSsl\eProviders\ApiProvider;
use AddonModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\KeyToIdMapping;
use AddonModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\Products;
use AddonModule\RealtimeRegisterSsl\eRepository\whmcs\service\SSL as SSLRepo;
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
use function Symfony\Component\Translation\t;

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

    public function __construct($params)
    {
        $this->p = array_merge($_POST, $params);
        if (!isset($this->p['model'])) {
            $this->p['model'] = \WHMCS\Service\Service::find($this->p['serviceid']);
        }

        $this->invoiceGenerator = new Invoice();
    }

    public function run()
    {
        try {
            SansDomains::decodeSanApproverEmailsAndMethods($this->p);
            $this->setSansDomainsDcvMethod();
            $this->SSLStepThree();
        } catch (Exception $ex) {
            //$this->redirectToStepOne($ex->getMessage());
            throw $ex;
        }
    }


    private function setSansDomainsDcvMethod(): void
    {
        $this->p['sansDomainsDcvMethod'] = [];
        foreach ($this->p['dcvmethod'] ?? [] as $domain => $dcvMethod) {
            if ($domain) {
                $this->p['sansDomainsDcvMethod'][] = [
                    "commonName" => $domain,
                    "type" => $dcvMethod,
                    "email" => $dcvMethod === 'EMAIL' ? $this->getApproverEmail($domain) : null
                ];
            }
        }
    }

    private function getApproverEmail(string $domain) {
        if ($this->p['approveremails'][$domain]) {
            return $this->p['approveremails'][$domain];
        }

        return explode('@', $this->p['approveremail'])[0]
            . '@'
            . preg_replace('/^\*\.|^www\./', '', $domain);
    }

    private function SSLStepThree()
    {
        if ($_REQUEST['action'] === 'redirectToStepOne') {
            $this->redirectToStepOne();
        }
        $this->loadSslConfig();
        $this->loadApiProduct();
        $this->orderCertificate();
    }

    private function loadSslConfig()
    {
        $repo = new SSLRepo();
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
        if (!empty($this->p[ConfigOptions::API_PRODUCT_ID])) {
            $apiRepo = new Products();
            $apiProduct = $apiRepo->getProduct(KeyToIdMapping::getIdByKey($this->p[ConfigOptions::API_PRODUCT_ID]));
        }

        $authKey = $this->p[ConfigOptions::AUTH_KEY_ENABLED];

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

            $order['dcv'] = $this->p['sansDomainsDcvMethod'];
        }

        $order['dcv'][] = [
            'commonName' => $csrDecode['commonName'],
            'type' => $this->p['dcvmethodMainDomain'],
            'email' => $this->p['approveremail'] ?? null
        ];
        $order['dcv'] = $this->mapDcv($order['dcv']);

        if ($this->p['fields']['org_division'] !== '') {
            $order['department'] = $this->p['fields']['org_division'];
        }

        $logs = new LogsRepo();

        if ($authKey) {
            $authKey = $this->processAuthKeyValidation(
                $csrDecode['commonName'],
                $apiProduct->product,
                $order['csr'],
                $order['dcv']
            );
        }

        $addedSSLOrder = $this->tryOrder($order, $csrDecode['commonName'], $authKey);

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
        $this->sslConfig->setStatus(\AddonModule\RealtimeRegisterSsl\eModels\whmcs\service\SSL::CONFIGURATION_SUBMITTED);
        $this->sslConfig->setCrt('--placeholder--');
        $this->sslConfig->setPrivateKey($this->p['privateKey']);
        $this->sslConfig->setCsr(trim($this->p['configdata']['csr']));
        $this->sslConfig->setDomain($orderDetails->identifier);
        $this->sslConfig->setOrderStatusDescription($orderDetails->status);
        $this->sslConfig->setApproverMethod($this->p['approvalmethod']);
        $this->sslConfig->setDcvMethod($this->p['dcvmethodMainDomain'] == 'http'?'FILE':$this->p['dcvmethodMainDomain']);
        $this->sslConfig->setProductId($this->p['configoption1']);
        $this->sslConfig->setSSLStatus($orderDetails->status);

        // Gets overwritten by whmcs ioncube encoded stuff atm >:(
        $this->sslConfig->save();

        //try to mark previous order as completed if it is autoinvoiced and autocreated product
        $this->invoiceGenerator->markPreviousOrderAsCompleted($this->p['serviceid']);

        FlashService::set('REALTIMEREGISTERSSL_WHMCS_SERVICE_TO_ACTIVE', $this->p['serviceid']);
        Invoice::insertDomainInfoIntoInvoiceItemDescription($this->p['serviceid'], $csrDecode['commonName']);

        $sslRepo = new SSLRepo();
        $sslService = $sslRepo->getByServiceId($this->p['serviceid']);

        $orderRepo = new OrderRepo();
        $orderRepo->addOrder(
            $this->p['userid'],
            $this->p['serviceid'],
            $sslService->id,
            $this->p['dcvmethodMainDomain'],
            'Pending Verification',
            array_merge((array) $this->sslConfig->configdata, $addedSSLOrder->toArray())
        );

        $logs->addLog($this->p['userid'],
            $this->p['serviceid'],
            'success',
            'The order has been placed ' . ($addedSSLOrder->certificateId ? 'and issued immediately.' : '.')
        );
        $this->processDcvEntries($addedSSLOrder->validations?->dcv?->toArray() ?? []);

        (new UpdateConfigData($sslService))->run();
    }

    private function redirectToStepOne($error = null)
    {
        if ($error) {
            $_SESSION['realtimeregister_ssl_FLASH_ERROR_STEP_ONE'] = $error;
        }
        header('Location: configuressl.php?cert=' . $_GET['cert']);
        die();
    }

    private function tryOrder(array $orderData, string $commonName, bool $authKey) {
        $sslOrder = [
            'customer' => ApiProvider::getCustomer(),
            'product' => $orderData['product'],
            'period' => $orderData['period'],
            'csr' => $orderData['csr'],
            'san' => empty($orderData['san']) ? null : $orderData['san'],
            'organization' => $orderData['organization'],
            'department' => $orderData['department'],
            'address' => $orderData['address'],
            'postalCode' => $orderData['postalCode'],
            'city' => $orderData['city'],
            'coc' => $orderData['coc'],
            'saEmail' => $orderData['approver']['email'],
            'approver' => $orderData['approver'],
            'country' => $orderData['country'],
            'language' => null,
            'dcv' => $orderData['dcv'],
            'domainName' => $commonName,
            'authKey' => $authKey,
            'state' => $orderData['state'],
        ];
        return $this->sendRequest($sslOrder);
    }

    private function sendRequest(array $sslOrder) {
        $logs = new LogsRepo();
        try {
            return ApiProvider::getInstance()
                ->getApi(CertificatesApi::class)
                ->requestCertificate(...$sslOrder);
        } catch (BadRequestException $exception) {
            $logs->addLog(
                $this->p['userid'], $this->p['serviceid'],
                'error',
                '[' . $sslOrder['domainName'] . '] Error:' . $exception->getMessage()
            );
            $decodedMessage = json_decode(str_replace('Bad Request: ', '', $exception->getMessage()), true);
            $retry = false;
            switch ($decodedMessage['type']) {
                case 'ObjectExists':
                    $reason = 'The request already exists';
                    break;
                case 'ValidationError':
                    $reason = 'Validation error: ' . $decodedMessage['message'];
                    $retry = true;
                    break;
                case 'ConstraintViolationException':
                    $violations = array_map(
                        fn($violation) => 'field: ' . $violation['field'] . ', message: ' . $violation['message'],
                        $decodedMessage['violations']
                    );
                    $reason = "Constraint Violation: <br/>" . implode("<br/>", $violations);
                    break;
                default:
                    $retry = true;
                    $reason = $exception->getMessage();
            }

            if ($retry && $sslOrder['authKey']) {
                return $this->sendRequest([...$sslOrder, 'authKey' => false]);
            }

            if ($this->p['noRedirect']) {
                throw $exception;
            }

            $this->redirectToStepOne($reason);
        }
    }
}
