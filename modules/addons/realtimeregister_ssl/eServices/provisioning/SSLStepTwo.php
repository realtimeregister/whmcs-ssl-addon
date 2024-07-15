<?php

namespace MGModule\RealtimeRegisterSsl\eServices\provisioning;

use Illuminate\Database\Capsule\Manager as Capsule;
use Exception;
use MGModule\RealtimeRegisterSsl\eHelpers\Domains;
use MGModule\RealtimeRegisterSsl\eHelpers\SansDomains;
use MGModule\RealtimeRegisterSsl\eProviders\ApiProvider;
use MGModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\KeyToIdMapping;
use MGModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\Products;
use MGModule\RealtimeRegisterSsl\eRepository\whmcs\service\SSLTemplorary;
use MGModule\RealtimeRegisterSsl\eServices\FlashService;
use MGModule\RealtimeRegisterSsl\mgLibs\Lang;
use MGModule\RealtimeRegisterSsl\models\apiConfiguration\Repository;
use MGModule\RealtimeRegisterSsl\models\whmcs\product\Product;
use MGModule\RealtimeRegisterSsl\models\whmcs\service\Service;
use SandwaveIo\RealtimeRegister\Api\CertificatesApi;

class SSLStepTwo
{
    private $p;
    private $errors = [];
    private $csrDecode = [];

    public function __construct(&$params)
    {
        $productssl = false;
        $checkTable = Capsule::schema()->hasTable(Products::MGFW_REALTIMEREGISTERSSL_PRODUCT_BRAND);
        if ($checkTable) {
            if (Capsule::schema()->hasColumn(Products::MGFW_REALTIMEREGISTERSSL_PRODUCT_BRAND, 'data')) {
                $productsslDB = Capsule::table(
                    Products::MGFW_REALTIMEREGISTERSSL_PRODUCT_BRAND
                )->where('pid', KeyToIdMapping::getIdByKey($params['configoption1']))->first();
                if (isset($productsslDB->data)) {
                    $productssl['product'] = json_decode($productsslDB->data, true);
                }
            }
        }

        if (isset($productssl['product_san_wildcard']) && $productssl['product_san_wildcard'] == 'yes') {
            $this->additional_san_validation[] = $params['configoption1'];
        }

        $this->p = &$params;
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

        $service = new Service($this->p['serviceid']);
        $product = new Product($service->productID);

        $productssl = false;
        $checkTable = Capsule::schema()->hasTable(Products::MGFW_REALTIMEREGISTERSSL_PRODUCT_BRAND);
        if ($checkTable) {
            if (Capsule::schema()->hasColumn(Products::MGFW_REALTIMEREGISTERSSL_PRODUCT_BRAND, 'data')) {
                $productsslDB = Capsule::table(Products::MGFW_REALTIMEREGISTERSSL_PRODUCT_BRAND)
                    ->where('pid', $product->id)->first();
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
        $ValidationMethods = ['email', 'dns', 'file'];


        if (empty($this->csrDecode)) {
            // Use server to generate csr..
            try {
                $this->csrDecode = ApiProvider::getInstance()->getApi(CertificatesApi::class)
                    ->decodeCsr(trim(rtrim($_POST['csr'])));
            } catch (Exception $e) {
                dd($e);
            }
        }
        $decodedCSR = $this->csrDecode;

        $_SESSION['csrDecode'] = $decodedCSR;
        $step2js = new SSLStepTwoJS($this->p);
        $mainDomain = $decodedCSR['commonName'];

        if (empty($mainDomain)) {
            $mainDomain = $decodedCSR['altNames'][0];
        }

        $domains = $mainDomain . PHP_EOL . $_POST['fields']['sans_domains']; // . implode(PHP_EOL, $decodedCSR['altNames'] ?: [], );
        $sansDomains = SansDomains::parseDomains(strtolower($domains));
        $approveremails = $step2js->fetchApprovalEmailsForSansDomains($sansDomains);

        $_SESSION['approveremails'] = $approveremails;

        return [
            'approveremails' => 'loading...',
            'approveremails2' => $approveremails,
            'approvalmethods' => $ValidationMethods,
            'brand' => $productssl->brand
        ];
    }

    public function setPrivateKey($privKey)
    {
        $this->p['privateKey'] = $privKey;
    }

    private function redirectToStepThree()
    {
        $tokenInput = generate_token();
        preg_match("/value=\"(.*)\\\"/", $tokenInput, $match);
        $token = $match[1];

        ob_clean();
        header('Location: configuressl.php?cert=' . $_GET['cert'] . '&step=3&token=' . $token);
        die();
    }

    private function SSLStepTwo()
    {
        SSLTemplorary::getInstance()->setByParams($this->p);

        $this->storeFieldsAutoFill();
        $this->validateSansDomains();
        $this->validateSansDomainsWildcard();
        $this->validateFields();

        if ($this->p['producttype'] != 'hostingaccount') {
            $this->validateCSR();
        }
        if (isset($this->p['privateKey']) && $this->p['privateKey'] != null) {
            $privKey = decrypt($this->p['privateKey']);
            $generateSCR = new GenerateCSR($this->p, $_POST);
            $generateSCR->savePrivateKeyToDatabase($this->p['serviceid'], $privKey);
        }
    }

    private function validateSansDomainsWildcard()
    {
        $sansDomainsWildcard = $this->p['fields']['wildcard_san'];
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

    private function validateSansDomains()
    {
        $sansDomains = $this->p['fields']['sans_domains'];
        $sansDomains = SansDomains::parseDomains($sansDomains);

        $apiProductId = $this->p[ConfigOptions::API_PRODUCT_ID];

        $invalidDomains = Domains::getInvalidDomains($sansDomains);

        if (count($invalidDomains)) {
            throw new Exception(Lang::getInstance()->T('incorrectSans') . implode(', ', $invalidDomains));
        }

        $includedSans = (int)$this->p[ConfigOptions::PRODUCT_INCLUDED_SANS];

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

        $boughtSans = (int)$this->p['configoptions'][ConfigOptions::OPTION_SANS_COUNT];
        $sansLimit = $includedSans + $boughtSans;

        if (count($uniqueDomains) > $sansLimit) {
            throw new Exception(Lang::T('sanLimitExceeded'));
        }
    }

    private function validateCSR()
    {
        $csr = trim(rtrim($this->p['csr']));
        $this->csrDecode = ApiProvider::getInstance()->getApi(CertificatesApi::class)->decodeCsr($csr);
        $decodedCSR = $this->csrDecode;
        $_SESSION['csrDecode'] = $decodedCSR;
        $productssl = false;
        $checkTable = Capsule::schema()->hasTable(Products::MGFW_REALTIMEREGISTERSSL_PRODUCT_BRAND);
        if ($checkTable) {
            if (Capsule::schema()->hasColumn(Products::MGFW_REALTIMEREGISTERSSL_PRODUCT_BRAND, 'data')) {
                $productsslDB = Capsule::table(Products::MGFW_REALTIMEREGISTERSSL_PRODUCT_BRAND)
                    ->where('pid', $this->p['pid'])->first();
                if (isset($productsslDB->data)) {
                    $productssl['product'] = json_decode($productsslDB->data, true);
                }
            }
        }

        if (!$productssl) {
            $productssl = ApiProvider::getInstance()->getApi(false)->getProduct($this->p['configoption1']);
        }

        if ($productssl['product']['wildcard_enabled']) {
            if (
                strpos($decodedCSR['csrResult']['CN'], '*.') !== false
                || strpos($decodedCSR['csrResult']['dnsName(s)'][0], '*.') !== false
            ) {
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

    private function validateFields()
    {
        if (empty(trim($this->p['jobtitle']))) {
            $this->errors[] = Lang::T('adminJobTitleMissing');
        }
        if (empty(trim($this->p['orgname']))) {
            $this->errors[] = Lang::T('organizationNameMissing');
        }
        if (empty(trim($this->p['fields']['order_type']))) {
            $this->errors[] = Lang::T('orderTypeMissing');
        }
    }

    private function storeFieldsAutoFill()
    {
        $fields = [];

        $a = [
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
            'privateKey'
        ];

        $b = [
            'order_type',
            'sans_domains',
            'org_name',
            'org_division',
            'org_lei',
            'org_duns',
            'org_addressline1',
            'org_city',
            'org_country',
            'org_fax',
            'org_phone',
            'org_postalcode',
            'org_regions'
        ];

        foreach ($a as $value) {
            $fields[] = [
                'name' => $value,
                'value' => $this->p[$value]
            ];
        }

        foreach ($b as $value) {
            if ($value == 'fields[order_type]') {
                $fields[] = [
                    'name' => sprintf('%s', $value),
                    'value' => $this->p['fields']['order_type']
                ];
            } else {
                $fields[] = [
                    'name' => sprintf('fields[%s]', $value),
                    'value' => $this->p['fields'][$value]
                ];
            }
        }

        FlashService::setFieldsMemory($_GET['cert'], $fields);
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
