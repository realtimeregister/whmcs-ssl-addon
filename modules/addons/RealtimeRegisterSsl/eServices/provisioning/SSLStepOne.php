<?php

namespace MGModule\RealtimeRegisterSsl\eServices\provisioning;

use Exception;
use MGModule\RealtimeRegisterSsl\eModels\cpanelservices\Service;

class SSLStepOne {

    private $p;

    function __construct(&$params) {        
        $this->p = &$params;
    }

    public function run() {
        try {            
            return $this->SSLStepOne();
        } catch (Exception $e) {
            \MGModule\RealtimeRegisterSsl\eServices\FlashService::setStepOneError($this->getErrorForClient());
        }
    }

    private function SSLStepOne() {
        $fields['additionalfields'] = [];
        $apiProductId  = $this->p[ConfigOptions::API_PRODUCT_ID];
        $apiRepo       = new \MGModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\Products();
        $apiProduct    = $apiRepo->getProduct($apiProductId);
        if($apiProduct->brand == 'comodo')
        {
            $apiWebServers = array(
                array('id' => '35', 'software' => 'IIS'),
                array('id' => '-1', 'software' => 'Any Other')
            );
        }
        else 
        {
            $apiWebServers = array(
                array('id' => '18', 'software' => 'IIS'),
                array('id' => '18', 'software' => 'Any Other')
            );
        }

        $apiWebServersJSON         = json_encode($apiWebServers);
        $fillVarsJSON              = json_encode(\MGModule\RealtimeRegisterSsl\eServices\FlashService::getFieldsMemory($_GET['cert']));
        $sanEnabledForWHMCSProduct = $this->p[ConfigOptions::PRODUCT_ENABLE_SAN] === 'on';
        $sanWildcardEnabledForWHMCSProduct = $this->p[ConfigOptions::PRODUCT_ENABLE_SAN_WILDCARD] === 'on';

        $includedSans = (int) $this->p[ConfigOptions::PRODUCT_INCLUDED_SANS];
        $includedSansWildcard = (int) $this->p[ConfigOptions::PRODUCT_INCLUDED_SANS_WILDCARD];
        
        $boughtSans   = (int) $this->p['configoptions'][ConfigOptions::OPTION_SANS_COUNT];

        $orderTypes = ['new', 'renew'];
        
        $sansLimit    = $includedSans + $boughtSans;

        $sansLimit = 10;
        $apiConf = (new \MGModule\RealtimeRegisterSsl\models\apiConfiguration\Repository())->get();
        $displayCsrGenerator = $apiConf->display_csr_generator;
        
        if (!$sanEnabledForWHMCSProduct) {
            $sansLimit = 0;
        } 

        if ($sansLimit > 0 || $sanWildcardEnabledForWHMCSProduct == 'on') {
            $fields['additionalfields'][\MGModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\San::getTitle()] = \MGModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\San::getFields($sansLimit, $this->p['configoptions']['sans_wildcard_count']+$includedSansWildcard, $this->p);
        }
        if ($apiProduct->isOrganizationRequired()) {
            $fields['additionalfields'][\MGModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\Organization::getTitle()] = \MGModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\Organization::getFields();
        }
        $countriesForGenerateCsrForm = \MGModule\RealtimeRegisterSsl\eRepository\whmcs\config\Countries::getInstance()->getCountriesForMgAddonDropdown();

        //get selected default country for CSR Generator
        $defaultCsrGeneratorCountry = ($displayCsrGenerator) ? $apiConf->default_csr_generator_country : '';
        if(key_exists($defaultCsrGeneratorCountry, $countriesForGenerateCsrForm) AND $defaultCsrGeneratorCountry != NULL)
        {
            //get country name
            $elementValue = $countriesForGenerateCsrForm[$defaultCsrGeneratorCountry]/* . ' (default)'*/;            
            //remove country from list
            unset($countriesForGenerateCsrForm[$defaultCsrGeneratorCountry]);
            //insert default country on the begin of countries list
            $countriesForGenerateCsrForm = array_merge(array($defaultCsrGeneratorCountry => $elementValue), $countriesForGenerateCsrForm);
        }

        $wildCard = false;
        $apiProducts = \MGModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\Products::getInstance()->getAllProducts();
        if(isset($apiProducts[$this->p['configoption1']]->wildcard_enabled) && $apiProducts[$this->p['configoption1']]->wildcard_enabled == '1')
        {
            $wildCard = true;
        }

        $cPanelService = new Service();
        $domains = $cPanelService->getDomainByUser($this->p['userid']);

        $stepOneBaseScript    = \MGModule\RealtimeRegisterSsl\eServices\ScriptService::getStepOneBaseScript($apiProduct->brand, $domains);
        $orderTypeScript    = \MGModule\RealtimeRegisterSsl\eServices\ScriptService::getOrderTypeScript($orderTypes, $fillVarsJSON);
        $webServerTypeSctipt  = \MGModule\RealtimeRegisterSsl\eServices\ScriptService::getWebServerTypeSctipt($apiWebServersJSON);
        $autoFillFieldsScript = \MGModule\RealtimeRegisterSsl\eServices\ScriptService::getAutoFillFieldsScript($fillVarsJSON);
        $generateCsrModalScript = ($displayCsrGenerator) ? \MGModule\RealtimeRegisterSsl\eServices\ScriptService::getGenerateCsrModalScript($this->p['serviceid'], $fillVarsJSON, $countriesForGenerateCsrForm, array('wildcard' => $wildCard)) : '';
        //when server type is not selected exception
        if(isset($_POST['privateKey']) && $_POST['privateKey'] != null && empty(json_decode($fillVarsJSON))) {
            $autoFillPrivateKeyField = \MGModule\RealtimeRegisterSsl\eServices\ScriptService::getAutoFillPrivateKeyField($_POST['privateKey']);
        }
        //auto fill order type field
        if(isset($_POST['fields']['order_type']) && $_POST['fields']['order_type'] != null) {
            $autoFillOrderTypeField = \MGModule\RealtimeRegisterSsl\eServices\ScriptService::getAutoFillOrderTypeField($_POST['fields']['order_type']);
        }
        
        $fields['additionalfields']['<br />']['<br />'] = [
            'Description' => $stepOneBaseScript . $webServerTypeSctipt . $orderTypeScript . $autoFillFieldsScript . $generateCsrModalScript .$autoFillPrivateKeyField . $autoFillOrderTypeField,
        ];

        if(empty($fields['additionalfields']['SANs']))
        {
            unset($fields['additionalfields']['SANs']);
        }

        return $fields;

    }
    private function getErrorForClient() {
        return \MGModule\RealtimeRegisterSsl\mgLibs\Lang::getInstance()->T('canNotFetchWebServer');
    }
}
