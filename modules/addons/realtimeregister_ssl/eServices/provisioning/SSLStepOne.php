<?php

declare(strict_types=1);

namespace AddonModule\RealtimeRegisterSsl\eServices\provisioning;

use AddonModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\KeyToIdMapping;
use AddonModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\Organization;
use AddonModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\Products;
use AddonModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\San;
use AddonModule\RealtimeRegisterSsl\eRepository\whmcs\config\Countries;
use AddonModule\RealtimeRegisterSsl\eServices\FlashService;
use AddonModule\RealtimeRegisterSsl\eServices\ScriptService;
use AddonModule\RealtimeRegisterSsl\models\apiConfiguration\Repository;
use Exception;

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
            FlashService::setStepOneError($e->getMessage());
        }
    }

    private function SSLStepOne()
    {
        $fields['additionalfields'] = [];
        $apiProductId = $this->p[ConfigOptions::API_PRODUCT_ID];
        $apiRepo = new Products();
        $apiProduct = $apiRepo->getProduct(KeyToIdMapping::getIdByKey($apiProductId));

        $fillVars = FlashService::getFieldsMemory($_GET['cert']);
        $sanEnabledForWHMCSProduct = $this->p[ConfigOptions::PRODUCT_ENABLE_SAN] === 'on';
        $sanWildcardEnabledForWHMCSProduct = $this->p[ConfigOptions::PRODUCT_ENABLE_SAN_WILDCARD] === 'on';

        $period = intval($this->p['configoptions'][ConfigOptions::OPTION_PERIOD][0]);
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
            $client = $this->p['clientsdetails'];
            $fillVars = array_merge(
                array_filter(['fields[org_name]' => $client['companyname'],
                    'fields[org_addressline1]' => $client['address1'],
                    'fields[org_city]' => $client['city'],
                    'fields[org_country]' => Countries::getInstance()->getCountryNameByCode($client['country']),
                    'fields[org_postalcode]' => $client['postcode'],
                    'fields[org_region]' => $client['fullstate']]),
                array_filter($fillVars)
            );
        }
        $countriesForGenerateCsrForm = Countries::getInstance()->getCountriesForAddonDropdown();
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
        $autoFillFieldsScript = ScriptService::getAutoFillFieldsScript(json_encode($fillVars));
        $autoFillPrivateKeyField = null;
        $autoFillOrderTypeField = null;

        $generateCsrModalScript = ($displayCsrGenerator) ? ScriptService::getGenerateCsrModalScript(
            $this->p['serviceid'],
            json_encode($fillVars),
            $countriesForGenerateCsrForm,
            ['wildcard' => $wildCard]
        ) : '';

        if (isset($_POST['privateKey']) && $_POST['privateKey'] != null && empty($fillVars)) {
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
}
