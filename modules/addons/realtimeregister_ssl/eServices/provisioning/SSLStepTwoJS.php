<?php

namespace MGModule\RealtimeRegisterSsl\eServices\provisioning;

use Exception;
use Illuminate\Database\Capsule\Manager as Capsule;
use MGModule\RealtimeRegisterSsl\eHelpers\SansDomains;
use MGModule\RealtimeRegisterSsl\eHelpers\Whmcs;
use MGModule\RealtimeRegisterSsl\eProviders\ApiProvider;
use MGModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\Products;
use MGModule\RealtimeRegisterSsl\eRepository\whmcs\service\SSLTemplorary;
use MGModule\RealtimeRegisterSsl\eServices\FlashService;
use MGModule\RealtimeRegisterSsl\eServices\ScriptService;
use MGModule\RealtimeRegisterSsl\models\apiConfiguration\Repository;
use MGModule\RealtimeRegisterSsl\models\whmcs\product\Product;
use MGModule\RealtimeRegisterSsl\models\whmcs\service\Service;
use SandwaveIo\RealtimeRegister\Api\CertificatesApi;

class SSLStepTwoJS
{
    private $p;
    private array $domainsEmailApprovals = [];
    private $brand = '';
    private $disabledValidationMethods = [];
    private $csrDecode = []; 

    function __construct(&$params, $csrdecode = [])
    {
        $this->p = &$params;
    }

    public function run()
    {
        if (!$this->canRun()) {
            return '';
        }

        if (!$this->isValidModule()) {
            return '';
        }
        try {
            $this->setBrand($_POST);
            $this->setDisabledValidationMethods($_POST);
            
            $service = new Service($this->p['serviceid']);

            $product = new Product($service->productID);
            
            $productssl = false;
            $checkTable = Capsule::schema()->hasTable(Products::MGFW_REALTIMEREGISTERSSL_PRODUCT_BRAND);
            if ($checkTable) {
                if (Capsule::schema()->hasColumn(Products::MGFW_REALTIMEREGISTERSSL_PRODUCT_BRAND, 'data')) {
                    $productsslDB = Capsule::table(Products::MGFW_REALTIMEREGISTERSSL_PRODUCT_BRAND)
                        ->where('pid_identifier', $product->configuration()->text_name)->first();
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

            if (!$productssl['product']['dcv_email']) {
                array_push($this->disabledValidationMethods, 'email');
            }

            if (!$productssl['product']['dcv_dns']) {
                array_push($this->disabledValidationMethods, 'dns');
            }

            if (!$productssl['product']['dcv_http']) {
                array_push($this->disabledValidationMethods, 'http');
            }

            $this->SSLStepTwoJS($this->p);
            
            return ScriptService::getSanEmailsScript(
                json_encode($this->domainsEmailApprovals),
                json_encode(FlashService::getFieldsMemory($_GET['cert'])),
                json_encode($this->brand),
                json_encode($this->disabledValidationMethods)
            );
        } catch (Exception $ex) {
            return '';
        }
    }

    private function canRun()
    {
        if ($this->p['filename'] !== 'configuressl') {
            return false;
        }
        if ($_GET['step'] != 2) {
            return false;
        }
        return true;
    }    

    private function setBrand($params)
    {
        if (isset($params['sslbrand']) &&  $params['sslbrand'] != null){
            $this->brand = $params['sslbrand'];
        }
    }
    
    private function setDisabledValidationMethods($params)
    {
        $apiConf = (new Repository())->get();
        if ($apiConf->disable_email_validation) {
            array_push($this->disabledValidationMethods, 'email');
        }
    }
    
    private function isValidModule()
    {
        return SSLTemplorary::getInstance()->get($_GET['cert']) === true;
    }

    private function SSLStepTwoJS()
    {
        if (!isset($_SESSION['csrDecode']) || empty($_SESSION['csrDecode'])) {
            $this->csrDecode = ApiProvider::getInstance()->getApi(CertificatesApi::class)->decodeCsr(trim(rtrim($_POST['csr'])));
        } else {
             $this->csrDecode = $_SESSION['csrDecode'];
             unset($_SESSION['csrDecode']);
        }
        
        $decodedCSR = $this->csrDecode;
        
        Capsule::table('tblhosting')->where('id', $this->p['serviceid'])->update([
            'domain' => $decodedCSR['csrResult']['CN']
        ]);
        
        $service = new Service($this->p['serviceid']);
        $product = new Product($service->productID);
        
        $productssl = false;
        $checkTable = Capsule::schema()->hasTable(Products::MGFW_REALTIMEREGISTERSSL_PRODUCT_BRAND);
        if ($checkTable) {
            if (Capsule::schema()->hasColumn(Products::MGFW_REALTIMEREGISTERSSL_PRODUCT_BRAND, 'data')) {
                $productsslDB = Capsule::table(Products::MGFW_REALTIMEREGISTERSSL_PRODUCT_BRAND)->where(
                    'pid',
                    $product->configuration()->text_name
                )->first();
                if (isset($productsslDB->data))
                {
                    $productssl['product'] = json_decode($productsslDB->data, true);
                }
            }
        }

        if (!$productssl) {
            /** @var CertificatesApi $certificatesApi */
            $certificatesApi = ApiProvider::getInstance()->getApi(CertificatesApi::class);
            $productssl = $certificatesApi->getProduct($product->configuration()->text_name);
        }

        $mainDomain = '';
        if (isset($decodedCSR['csrResult']['CN'])) {
            $mainDomain       = $decodedCSR['csrResult']['CN'];
        }
        if (
            isset($decodedCSR['csrResult']['dnsName(s)'][0])
            && strpos($decodedCSR['csrResult']['dnsName(s)'][0], '*.') !== false
        ) {
            $mainDomain = $decodedCSR['csrResult']['dnsName(s)'][0];
        }

        $domains = $mainDomain . PHP_EOL . $_POST['fields']['sans_domains'];
        
        $sansDomains = SansDomains::parseDomains(strtolower($domains));
        $wildcardDomains = SansDomains::parseDomains(strtolower($_POST['fields']['wildcard_san']));
        
        if (isset($_SESSION['approveremails']) && !empty($_SESSION['approveremails'])) {
            $this->domainsEmailApprovals = $_SESSION['approveremails'];
            unset($_SESSION['approveremails']);
        } else {
            $this->fetchApprovalEmailsForSansDomains($sansDomains);
        }

        $this->fetchApprovalEmailsForSansDomains($wildcardDomains);

        if (isset($_POST['privateKey']) && $_POST['privateKey'] != null) {
            $privKey = decrypt($_POST['privateKey']);
            $GenerateSCR = new GenerateCSR($this->p, $_POST);
            $GenerateSCR->savePrivateKeyToDatabase($this->p['serviceid'], $privKey);
        }
    }

    public function fetchApprovalEmailsForSansDomains($sansDomains): array
    {
        foreach ($sansDomains as $sansDomain) {
            $this->domainsEmailApprovals[$sansDomain] = [];
            try {
                /** @var CertificatesApi $certificatesApi */
                $certificatesApi = ApiProvider::getInstance()->getApi(CertificatesApi::class);
                $apiDomainEmails = $certificatesApi->listDcvEmailAddresses($sansDomain);
            } catch (Exception $e) {
                continue;
            }
            $this->domainsEmailApprovals[$sansDomain] = $apiDomainEmails;
        }

        return $this->domainsEmailApprovals;
    }
}
