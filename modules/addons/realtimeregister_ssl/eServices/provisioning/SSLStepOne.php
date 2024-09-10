<?php

declare(strict_types=1);

namespace MGModule\RealtimeRegisterSsl\eServices\provisioning;

use Exception;
use MGModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\KeyToIdMapping;
use MGModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\Organization;
use MGModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\Products;
use MGModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\San;
use MGModule\RealtimeRegisterSsl\eRepository\whmcs\config\Countries;
use MGModule\RealtimeRegisterSsl\eServices\FlashService;
use MGModule\RealtimeRegisterSsl\eServices\ScriptService;
use MGModule\RealtimeRegisterSsl\mgLibs\Lang;
use MGModule\RealtimeRegisterSsl\models\apiConfiguration\Repository;

class SSLStepOne
{
    private $p;

    public function __construct(&$params)
    {
        $this->p = &$params;
    }

    public function run()
    {
        try {
            return $this->SSLStepOne();
        } catch (Exception $e) {
            FlashService::setStepOneError($this->getErrorForClient());
        }
    }

    private function SSLStepOne()
    {
        $fields['additionalfields'] = [];
        $apiProductId = $this->p[ConfigOptions::API_PRODUCT_ID];
        $apiRepo = new Products();
        $apiProduct = $apiRepo->getProduct(KeyToIdMapping::getIdByKey($apiProductId));

        $fillVarsJSON = json_encode(FlashService::getFieldsMemory($_GET['cert']));
        $sanEnabledForWHMCSProduct = $this->p[ConfigOptions::PRODUCT_ENABLE_SAN] === 'on';
        $sanWildcardEnabledForWHMCSProduct = $this->p[ConfigOptions::PRODUCT_ENABLE_SAN_WILDCARD] === 'on';

        $period = intval($this->p['configoptions']['years'][0]);
        $includedSans = (int)$this->p[ConfigOptions::PRODUCT_INCLUDED_SANS];
        $includedSansWildcard = (int)$this->p[ConfigOptions::PRODUCT_INCLUDED_SANS_WILDCARD];

        $boughtSans = (int)$this->p['configoptions'][ConfigOptions::OPTION_SANS_COUNT . $period];

        $sansLimit = $includedSans + $boughtSans;

        $apiConf = (new Repository())->get();
        $displayCsrGenerator = $apiConf->display_csr_generator;

        if (!$sanEnabledForWHMCSProduct) {
            $sansLimit = 0;
        }

        if ($sansLimit > 0 || $sanWildcardEnabledForWHMCSProduct == 'on') {
            $fields['additionalfields'][San::getTitle()] = San::getFields(
                $sansLimit,
                (int)$this->p['configoptions'][ConfigOptions::OPTION_SANS_WILDCARD_COUNT . $period]
                + $includedSansWildcard,
                $this->p
            );
        }
        if ($apiProduct->isOrganizationRequired()) {
            $fields['additionalfields'][Organization::getTitle()] = Organization::getFields();
        }
        $countriesForGenerateCsrForm = Countries::getInstance()->getCountriesForMgAddonDropdown();

        //get selected default country for CSR Generator
        $defaultCsrGeneratorCountry = ($displayCsrGenerator) ? $apiConf->default_csr_generator_country : '';
        if (
            key_exists($defaultCsrGeneratorCountry, $countriesForGenerateCsrForm)
            && $defaultCsrGeneratorCountry != null
        ) {
            //get country name
            $elementValue = $countriesForGenerateCsrForm[$defaultCsrGeneratorCountry]/* . ' (default)'*/
            ;
            //remove country from list
            unset($countriesForGenerateCsrForm[$defaultCsrGeneratorCountry]);
            //insert default country on the begin of countries list
            $countriesForGenerateCsrForm = array_merge(
                [$defaultCsrGeneratorCountry => $elementValue], $countriesForGenerateCsrForm
            );
        }

        $wildCard = false;
        $apiProducts = Products::getInstance()->getAllProducts();
        if (
            isset($apiProducts[$this->p['configoption1']]->wildcard_enabled)
            && $apiProducts[$this->p['configoption1']]->wildcard_enabled == '1'
        ) {
            $wildCard = true;
        }

        $domainsResult = localApi('GetClientsDomains', ['clientid' => $this->p['userid']]);

        $domains = [];
        foreach ($domainsResult['domains'] as $domain) {
            foreach ($domain as $d) {
                $domains[] = $d['domainname'];
            }
        }

        $stepOneBaseScript = ScriptService::getStepOneBaseScript($apiProduct->brand, $domains);
        $webServerTypeScript = ScriptService::getWebServerTypeScript();
        $autoFillFieldsScript = ScriptService::getAutoFillFieldsScript($fillVarsJSON);
        $autoFillPrivateKeyField = null;
        $autoFillOrderTypeField = null;

        $generateCsrModalScript = ($displayCsrGenerator) ? ScriptService::getGenerateCsrModalScript(
            $this->p['serviceid'],
            $fillVarsJSON,
            $countriesForGenerateCsrForm,
            ['wildcard' => $wildCard]
        ) : '';
        //when server type is not selected exception
        if (isset($_POST['privateKey']) && $_POST['privateKey'] != null && empty(json_decode($fillVarsJSON))) {
            $autoFillPrivateKeyField = ScriptService::getAutoFillPrivateKeyField($_POST['privateKey']);
        }
        //auto fill order type field
        if (isset($_POST['fields']['order_type']) && $_POST['fields']['order_type'] != null) {
            $autoFillOrderTypeField = ScriptService::getAutoFillOrderTypeField($_POST['fields']['order_type']);
        }

        $fields['additionalfields']['<br />']['<br />'] = [
            'Description' => $stepOneBaseScript . $webServerTypeScript
                . $autoFillFieldsScript . $generateCsrModalScript . $autoFillPrivateKeyField . $autoFillOrderTypeField,
        ];

        if (empty($fields['additionalfields']['SANs'])) {
            unset($fields['additionalfields']['SANs']);
        }

        return $fields;
    }

    private function getErrorForClient()
    {
        return Lang::getInstance()->T('canNotFetchWebServer');
    }
}
