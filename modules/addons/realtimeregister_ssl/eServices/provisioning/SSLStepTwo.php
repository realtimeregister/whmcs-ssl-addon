<?php

namespace AddonModule\RealtimeRegisterSsl\eServices\provisioning;

use AddonModule\RealtimeRegisterSsl\addonLibs\Lang;
use AddonModule\RealtimeRegisterSsl\eHelpers\Domains;
use AddonModule\RealtimeRegisterSsl\eHelpers\SansDomains;
use AddonModule\RealtimeRegisterSsl\eProviders\ApiProvider;
use AddonModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\Products;
use AddonModule\RealtimeRegisterSsl\eRepository\whmcs\service\SSLTemporary;
use AddonModule\RealtimeRegisterSsl\eServices\FlashService;
use AddonModule\RealtimeRegisterSsl\models\whmcs\service\Service;
use Exception;
use Illuminate\Database\Capsule\Manager as Capsule;
use RealtimeRegister\Api\CertificatesApi;

class SSLStepTwo
{
    use SSLUtils;
    private array $p;
    private int $pid;
    private array $errors = [];
    private array $csrDecode = [];
    private string $productName;

    public function __construct($params)
    {
        $this->p = $params;
        $this->pid = $params['productId'] ?: (new Service($this->p['serviceid']))->productID;
        $this->productName = $params[ConfigOptions::API_PRODUCT_ID];
    }

    public function run()
    {
        try {
            $this->SSLStepTwo();
        } catch (Exception $ex) {
            return ['error' => $ex->getMessage()];
        }

        if (!empty($this->errors)) {
            return ['error' => $this->errorsToWhmcsError()];
        }

        $productssl = false;
        $checkTable = Capsule::schema()->hasTable(Products::REALTIMEREGISTERSSL_PRODUCT_BRAND);
        if ($checkTable) {
            if (Capsule::schema()->hasColumn(Products::REALTIMEREGISTERSSL_PRODUCT_BRAND, 'data')) {
                $productsslDB = Capsule::table(Products::REALTIMEREGISTERSSL_PRODUCT_BRAND)
                    ->where('pid_identifier', $this->productName)->first();
                if (isset($productsslDB->data)) {
                    $productssl = json_decode($productsslDB->data, true);
                }
            }
        }

        if (!$productssl) {
            /** @var CertificatesApi $certificatesApi */
            $certificatesApi = ApiProvider::getInstance()->getApi(CertificatesApi::class);
            $productssl = $certificatesApi->getProduct($this->productName)->toArray();
        }
        $validationMethods = ['email', 'dns', 'file'];

        if (empty($this->csrDecode)) {
            // Use server to generate csr..
            $this->csrDecode = ApiProvider::getInstance()->getApi(CertificatesApi::class)
                ->decodeCsr(trim($_POST['csr']));
        }
        $decodedCSR = $this->csrDecode;

        $_SESSION['csrDecode'] = $decodedCSR;
        $step2js = new SSLStepTwoJS($this->p);
        $mainDomain = $decodedCSR['commonName'];

        if (empty($mainDomain)) {
            $mainDomain = $decodedCSR['san'][0];
        }

        $domains = $mainDomain
            . PHP_EOL
            . $_POST['fields']['sans_domains']
            . PHP_EOL
            . $_POST['fields']['wildcard_san'];
        $sansDomains = SansDomains::parseDomains(strtolower($domains));
        $approveremails = $step2js->fetchApprovalEmailsForSansDomains($sansDomains);

        $_SESSION['approveremails'] = $approveremails;

        return [
            'commonName' => $mainDomain,
            'approveremails' => 'loading...',
            'approveremails2' => $approveremails,
            'approvalmethods' => $validationMethods,
            'brand' => $productssl['brand']
        ];
    }

    public function setPrivateKey($privKey)
    {
        $this->p['privateKey'] = $privKey;
    }

    private function SSLStepTwo()
    {
        SSLTemporary::getInstance()->setByParams($this->p);

        $this->validateCSR();
        $this->storeFieldsAutoFill();
        $this->validateSansDomains();
        $this->validateSansDomainsWildcard();

        if (isset($this->p['privateKey']) && $this->p['privateKey'] != null) {
            $privKey = decrypt($this->p['privateKey']);
            $generateSCR = new GenerateCSR($this->p, $_POST);
            $generateSCR->savePrivateKeyToDatabase($this->p['serviceid'], $privKey);
        }
    }

    private function validateSansDomainsWildcard()
    {
        $sansDomainsWildcard = SansDomains::parseDomains($this->p['fields']['wildcard_san']);

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

    private function validateSansDomains()
    {
        $sansDomains = $this->p['fields']['sans_domains'];
        $sansDomains = SansDomains::parseDomains($sansDomains);

        $invalidDomains = Domains::getInvalidDomains($sansDomains);

        if (count($invalidDomains)) {
            throw new Exception(Lang::getInstance()->T('incorrectSans') . implode(', ', $invalidDomains));
        }

        $productBrandRepository = Products::getInstance();
        $productBrand = $productBrandRepository->getProductByName($this->productName);

        $commonName = $this->csrDecode['commonName'];
        $sanCount = $this->getSanDomainCount($sansDomains, $commonName, $productBrand);

        $sansLimit = $this->getSansLimit($this->p);

        if ($sanCount > $sansLimit) {
            throw new Exception(Lang::T('sanLimitExceeded'));
        }
    }

    private function validateCSR()
    {
        $csr = trim($this->p['csr']);

        $this->csrDecode = ApiProvider::getInstance()->getApi(CertificatesApi::class)->decodeCsr($csr);
        $decodedCSR = $this->csrDecode;
        $_SESSION['csrDecode'] = $decodedCSR;
        $productssl = false;
        $checkTable = Capsule::schema()->hasTable(Products::REALTIMEREGISTERSSL_PRODUCT_BRAND);
        if ($checkTable) {
            if (Capsule::schema()->hasColumn(Products::REALTIMEREGISTERSSL_PRODUCT_BRAND, 'data')) {
                $productsslDB = Capsule::table(Products::REALTIMEREGISTERSSL_PRODUCT_BRAND)
                    ->where('pid_identifier', $this->productName)->first();
                if (isset($productsslDB->data)) {
                    $productssl = json_decode($productsslDB->data, true);
                }
            }
        }

        if (!$productssl) {
            $productssl = ApiProvider::getInstance()
                ->getApi(CertificatesApi::class)
                ->getProduct($this->p['configoption1'])
                ->toArray();
        }

        if ($productssl['certificateType'] === 'WILDCARD') {
            if (str_contains($decodedCSR['csrResult']['CN'], '*.')) {
                return true;
            } else {
                if (isset($decodedCSR['csrResult']['errorMessage'])) {
                    throw new Exception($decodedCSR['csrResult']['errorMessage']);
                }

                throw new Exception(Lang::T('incorrectCSR'));
            }
        }

        if (isset($decodeCSR['csrResult']['errorMessage'])) {
            if (isset($decodeCSR['csrResult']['CN']) && strpos($decodeCSR['csrResult']['CN'], '*.') !== false) {
                return true;
            }

            throw new Exception($decodeCSR['csrResult']['errorMessage']);
        }
    }

    private function storeFieldsAutoFill()
    {
        $fields = [];

        $baseFields = [
            'servertype',
            'csr',
            'firstname',
            'lastname',
            'orgname',
            'jobtitle',
            'email',
            'address1',
            'address2',
            'city',
            'state',
            'postcode',
            'country',
            'phonenumber',
            'privateKey',
            'custom-hostname',
            'step-type-data-domain',
            'dcvmethodMainDomain',
            'approveremail'
        ];

        $additional = [
            'sans_domains',
            'wildcard_san',
            'org_name',
            'org_coc',
            'org_addressline1',
            'org_city',
            'org_country',
            'org_postalcode',
            'org_region'
        ];

        foreach ($baseFields as $value) {
            $fields[$value] = $this->p[$value];
        }

        foreach ($additional as $value) {
            $fields[sprintf('fields[%s]', $value)] = $this->p[$value] ?? $this->p['fields'][$value];
        }

        if ($_GET['cert']) {
            FlashService::setFieldsMemory($_GET['cert'], $fields);
        } else {
            FlashService::setFieldsMemory($this->csrDecode['commonName'], $fields);
        }
    }

    private function errorsToWhmcsError()
    {
        $i = 0;
        $err = '';

        if (count($this->errors) === 1) {
            return $this->errors[0];
        }

        foreach ($this->errors as $error) {
            if ($i === 0) {
                $err .= $error . '</li>';
            } else {
                $err .= '<li>' . $error . '</li>';
            }
            $i++;
        }
        return $err;
    }
}
