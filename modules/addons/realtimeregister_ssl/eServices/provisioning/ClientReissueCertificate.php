<?php

namespace AddonModule\RealtimeRegisterSsl\eServices\provisioning;

use AddonModule\RealtimeRegisterSsl\addonLibs\Lang;
use AddonModule\RealtimeRegisterSsl\eHelpers\Domains;
use AddonModule\RealtimeRegisterSsl\eHelpers\Invoice;
use AddonModule\RealtimeRegisterSsl\eHelpers\SansDomains;
use AddonModule\RealtimeRegisterSsl\eModels\whmcs\service\SSL;
use AddonModule\RealtimeRegisterSsl\eProviders\ApiProvider;
use AddonModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\KeyToIdMapping;
use AddonModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\Products;
use AddonModule\RealtimeRegisterSsl\eRepository\whmcs\config\Countries;
use AddonModule\RealtimeRegisterSsl\eRepository\whmcs\service\SSL as SSLRepo;
use AddonModule\RealtimeRegisterSsl\eServices\ScriptService;
use AddonModule\RealtimeRegisterSsl\eServices\TemplateService;
use AddonModule\RealtimeRegisterSsl\models\apiConfiguration\Repository;
use AddonModule\RealtimeRegisterSsl\models\logs\Repository as LogsRepo;
use AddonModule\RealtimeRegisterSsl\models\whmcs\product\Product;
use AddonModule\RealtimeRegisterSsl\models\whmcs\service\Service;
use Exception;
use Illuminate\Database\Capsule\Manager as Capsule;
use RealtimeRegister\Api\CertificatesApi;
use RealtimeRegister\Exceptions\BadRequestException;

class ClientReissueCertificate
{
    use SSLUtils;
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
     * @var SSL
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
        SansDomains::decodeSanApproverEmailsAndMethods($_POST);
        $this->setMainDomainDcvMethod($_POST);
        $this->setSansDomainsDcvMethod($_POST);
        return $this->reissueController();
    }

    private function reissueController()
    {
        try {
            $this->validateService();
        } catch (Exception $ex) {
            return '- ' . \AddonModule\RealtimeRegisterSsl\eHelpers\Exception::e($ex);
        }
        if (isset($this->post['stepOneForm'])) {
            try {
                $this->stepOneForm();
                $ssl = new SSLRepo();
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

        $this->vars['generateCsrIntegrationCode'] = ($displayCsrGenerator) ? ScriptService::getGenerateCsrModalScript(
            $this->p['serviceid'],
            json_encode([]),
            $countriesForGenerateCsrForm
        ) : '';
        $this->vars['serviceID'] = $this->p['serviceid'];

        $this->vars['sansLimit'] = $this->getSansLimit($this->p);
        $this->vars['sansLimitWildCard'] = $this->getSansLimitWildcard($this->p);

        $ssl = new SSLRepo();
        $ssldata = $ssl->getByServiceId($this->p['serviceid']);
        $this->vars['csrreissue'] = $ssldata->configdata->csr;

        $sanSingle = [];
        $sanWildcard = [];

        $this->vars['privKey'] = '';
        if (isset($ssldata->configdata->private_key) && !empty($ssldata->configdata->private_key)) {
            $this->vars['privKey'] = $ssldata->configdata->private_key;
        }

        $allSans = $ssldata->getSanDetails();

        foreach ($allSans as $san) {
            if (strpos($san->san_name, '*.') !== false) {
                $sanWildcard[] = $san->san_name;
            } else {
                $sanSingle[] = $san->san_name;
            }
        }

        $sanSingle = implode(PHP_EOL, $sanSingle);
        $sanWildcard = implode(PHP_EOL, $sanWildcard);

        $this->vars['sandetails']['sans_domains'] = $sanSingle;
        $this->vars['sandetails']['wildcard_san'] = $sanWildcard;

        return $this->build(self::STEP_ONE);
    }

    private function setApproverData(SSL $sslData) {
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
        $this->vars['coc'] = $configData->coc;
        $this->vars['orgname'] = $cert->organization;
        $this->vars['city'] = $cert->city;
        $this->vars['state'] = $cert->state;
        $this->vars['country'] = $cert->country;
        $this->vars['address'] = implode("\n", $cert->addressLine);
        $this->vars['postcode'] = $cert->postalCode;
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

    /**
     * @throws Exception
     */
    private function stepOneForm()
    {
        $decodeCSR = ApiProvider::getInstance()->getApi(CertificatesApi::class)->decodeCsr($this->post['csr']);
        $this->validateSanDomains($decodeCSR['commonName']);
        $this->validateSansDomainsWildcard();


        $_SESSION['decodeCSR'] = $decodeCSR;

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
    }

    private function stepTwoForm()
    {
        if (!empty($_SESSION['decodeCSR'])) {
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
        $csr = $orderData['csr'] = $this->post['csr'];

        $sansDomains = [];

        if ($this->getSansLimit($this->p)) {
            $sansDomains = SansDomains::parseDomains($this->post['sans_domains']);
            $sansDomainsWildcard = SansDomains::parseDomains($this->post['sans_domains_wildcard']);
            $sansDomains = array_merge($sansDomains, $sansDomainsWildcard);


            if (!empty($sanDcvMethods = $this->getSansDomainsValidationMethods())) {
                $i = 0;
                foreach ($_POST['approveremails'] as $domain => $approveremail) {
                    $dcv[] = [
                        "commonName" => $domain,
                        "type" => self::getDcvMethod($sanDcvMethods[$i]),
                        "email" => $approveremail
                    ];
                    $i++;
                }
            }
        }
        $orderData['dcv'] = $dcv;
        $orderData['san'] = empty($sansDomains) ? null : $sansDomains;

        if ($this->post['extraValidation']) {
            $orderData['organization'] = $this->post['orgname'];
            $orderData['address'] = $this->post['address'];
            $orderData['postalCode'] = $this->post['postcode'];
            $orderData['city'] = $this->post['city'];
            $orderData['state'] = $this->post['state'];
            $orderData['approver'] = [
                "firstName" => $this->post['firstname'],
                "lastName" => $this->post['lastname'],
                "jobTitle" => $this->post['jobtitle'] ?: null,
                "email" => $this->post['email'],
                "voice" => str_replace(" ", "",
                    '+' . $this->post['country-calling-code-phonenumber'] . '.' . $this->post['phonenumber'])
            ];
        }

        $authKey = $this->p[ConfigOptions::AUTH_KEY_ENABLED];
        if ($authKey) {
            $apiRepo = new Products();
            $apiProduct = $apiRepo->getProduct(KeyToIdMapping::getIdByKey($this->p[ConfigOptions::API_PRODUCT_ID]));
            $authKey = $this->processAuthKeyValidation($commonName, $apiProduct->product, $csr, $dcv);
        }

        $reissueData = $this->tryOrder($this->sslService->getCertificateId(), $orderData, $commonName, $authKey);

        $this->sslService->setRemoteId($reissueData->processId);

        $logs = new LogsRepo();
        $this->processDcvEntries($reissueData->validations?->dcv?->toArray() ?? []);

        $this->sslService->setCrt('--placeholder--');
        $this->sslService->setRemoteId($reissueData->processId);
        $this->sslService->setCa(null);
        $this->sslService->status = SSL::CONFIGURATION_SUBMITTED;
        $this->sslService->setConfigdataKey('csr', $csr);

        if (isset($_POST['privateKey']) && $_POST['privateKey'] != null) {
            $this->sslService->setPrivateKey($_POST['privateKey']);
        }

        $this->sslService->save();

        try {
            $configDataUpdate = new UpdateConfigData($this->sslService);
            $configDataUpdate->run();
        } catch (Exception $e) {
            $logs->addLog(
                $this->p['userid'],
                $this->p['serviceid'],
                'error',
                '[' . $commonName . '] Error:' . $e->getMessage()
            );
            $this->sslService->setSSLStatus('SUSPENDED');
            $this->sslService->save();
        }

        $logs->addLog($this->p['userid'],
            $this->p['serviceid'],
            'success',
            'The reissue order has been placed' . ($authKey ? ', the certificate was issued immediately.' : '.')
        );

        try {
            Invoice::insertDomainInfoIntoInvoiceItemDescription($this->p['serviceid'], $decodedCSR['commonName'], true);
        } catch (Exception $e) {
        }
    }

    private static function getDcvMethod(string $dcvMethod) : string {
        return strtolower($dcvMethod) == 'http' ? 'FILE' : strtoupper($dcvMethod);
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

    private function validateSanDomains(string $commonName)
    {
        $sansDomains = $this->post['sans_domains'];
        $sansDomains = SansDomains::parseDomains($sansDomains);

        $apiProductId = $this->p[ConfigOptions::API_PRODUCT_ID];

        $productBrandRepository = Products::getInstance();
        $productBrand = $productBrandRepository->getProduct(KeyToIdMapping::getIdByKey($apiProductId));

        $sanCount = $this->getSanDomainCount($sansDomains, $commonName, $productBrand);
        $sansLimit = $this->getSansLimit($this->p);
        if ($sanCount > $sansLimit) {
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

        $sansLimit = $this->getSansLimitWildcard($this->p);
        if (count($sansDomainsWildcard) > $sansLimit) {
            throw new Exception(Lang::T('sanLimitExceededWildcard'));
        }
    }

    private function validateService()
    {
        $ssl = new SSLRepo();
        $this->sslService = $ssl->getByServiceId($this->p['serviceid']);
        if (is_null($this->sslService)) {
            throw new Exception(Lang::getInstance()->T('createNotInitialized'));
        }

        if (!in_array($this->sslService->configdata->ssl_status, ['ACTIVE', 'COMPLETED'])) {
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

    private function tryOrder(int $certificateId, array $orderData, string $commonName, bool $authKey) {
        $sslOrder = [
            'csr' => $orderData['csr'],
            'san' => empty($orderData['san']) ? null : $orderData['san'],
            'organization' => $orderData['organization'],
            'department' => $orderData['department'],
            'address' => $orderData['address'],
            'postalCode' => $orderData['postalCode'],
            'city' => $orderData['city'],
            'coc' => $orderData['coc'],
            'approver' => $orderData['approver'],
            'country' => null,
            'language' => null,
            'dcv' => $orderData['dcv'],
            'domainName' => $commonName,
            'authKey' => $authKey,
            'state' => $orderData['state'],
        ];
        return $this->sendRequest($certificateId, $sslOrder);
    }

    private function sendRequest(int $certificateId, array $sslOrder)
    {
        $logs = new LogsRepo();
        try {
            return ApiProvider::getInstance()
                ->getApi(CertificatesApi::class)
                ->reissueCertificate($certificateId, ...$sslOrder);
        } catch (BadRequestException $exception) {
            $logs->addLog(
                $this->p['userid'], $this->p['serviceid'],
                'error',
                '[' . $sslOrder['domainName'] . '] Error:' . $exception->getMessage()
            );
            $decodedMessage = json_decode(str_replace('Bad Request: ', '', $exception->getMessage()), true);
            $retry = false;
            switch ($decodedMessage['type']) {
                case 'ConstraintViolationException':
                case 'ObjectExists':
                    break;
                default:
                    $retry = true;
                    break;
            }

            if ($retry && $sslOrder['authKey']) {
                return $this->sendRequest($certificateId, [...$sslOrder, 'authKey' => false]);
            }
            throw $exception;
        }
    }
}
