<?php

namespace MGModule\RealtimeRegisterSsl\eServices\provisioning;

use Exception;
use MGModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\Products;
use MGModule\RealtimeRegisterSsl\eServices\ScriptService;
use WHMCS\Application;

/**
 * Types:
 * 
 * * text
 * * password
 * * yesno
 * * dropdown
 * * radio
 * * textarea
 */
class ConfigOptions
{
    private $p;
    public const API_PRODUCT_ID = 'configoption1';
    public const API_PRODUCT_MONTHS = 'configoption2';
    public const PRODUCT_ENABLE_SAN = 'configoption3';
    public const PRODUCT_ENABLE_SAN_WILDCARD = 'configoption13';
    public const PRODUCT_INCLUDED_SANS = 'configoption4';
    public const PRICE_AUTO_DOWNLOAD = 'configoption5';
    public const COMMISSION = 'configoption6';
    public const MONTH_ONE_TIME = 'configoption7';
    public const PRODUCT_INCLUDED_SANS_WILDCARD = 'configoption8';
    public const OPTION_SANS_COUNT = 'sans_count'; // sans_count|SANs http://puu.sh/vXXx3/d08fdb2c2f.png
    public const OPTION_SANS_WILDCARD_COUNT = 'sans_wildcard_count';

    public function __construct(&$params = null)
    {
        $this->p = &$params;
    }

    public function run()
    {
        try {
            return $this->getConfigOptions();
        } catch (Exception $ex) {
            return $this->getErrorOptions($ex->getMessage());
        }
    }

    private function getConfigOptions()
    {
        $apiProducts = Products::getInstance()->getAllProducts();
        $products = [];

        foreach ($apiProducts as $apiProduct) {
            $products[$apiProduct->id] = $apiProduct->product;
        }

        return $this->getFields($products);
    }

    private function getFields($products)
    {
        return [
            'Certificate Type' => [
                'Type' => 'dropdown',
                'Options' => $products
            ],
            'Months' => [
                'Type' => 'text'
            ],
            'Enable SANs' => [
                'Type' => 'yesno',
            ],
            'Included SANs' => [
                'Type' => 'text',
            ],
            'PRICE AUTO DOWNLOAD' => [
                'Type' => 'text',
            ],
            'COMMISSION' => [
                'Type' => 'text',
                'Description' => '<script>$(function(){$("input[name=\"packageconfigoption[5]\"]").parent("td.fieldarea").parent("tr").hide();});</script>'
            ],
            'Months One Time' => [
                'Type' => 'text',
            ],
        ];
    }

    private function getErrorOptions($error)
    {
        return [
            'An Error Occurred:' => [
                'Type' => 'text',
                'Description' => ScriptService::getConfigOptionErrorScript($error)
            ]
        ];
    }

    public function validateAndSanitizeQuantityConfigOptions($configOption, array $configOptionsMinMaxQuantities)
    {
        $whmcs = Application::getInstance();
        $errorMessage = '';
        foreach ($configOption as $configid => $optionvalue) {
            if (!key_exists($configid, $configOptionsMinMaxQuantities))
                continue;
            $data = get_query_vals("tblproductconfigoptions", "", ["id" => $configid]);
            $optionname = $data["optionname"];
            $qtyminimum = ($configOptionsMinMaxQuantities[$configid]['min'] != null)
                ? $configOptionsMinMaxQuantities[$configid]['min'] : $data["qtyminimum"];
            $qtymaximum = ($configOptionsMinMaxQuantities[$configid]['max'] != null)
                ? $configOptionsMinMaxQuantities[$configid]['max'] : $data["qtymaximum"];
            if (strpos($optionname, "|")) {
                $optionname = explode("|", $optionname);
                $optionname = trim($optionname[1]);
            }
            $optionvalue = (int)$optionvalue;
            if ($qtyminimum < 0) {
                $qtyminimum = 0;
            }
            if (
                $optionvalue < 0 || $optionvalue < $qtyminimum && 0 < $qtyminimum
                || 0 < $qtymaximum && $qtymaximum < $optionvalue
            ) {
                if ($qtymaximum <= 0) {
                    $qtymaximum = $whmcs->get_lang("clientareaunlimited");
                }

                $errorMessage .= "<li>" . sprintf(
                    $whmcs->get_lang("configoptionqtyminmax"), $optionname, $qtyminimum, $qtymaximum
                );
            }
        }

        return $errorMessage;
    }
}
