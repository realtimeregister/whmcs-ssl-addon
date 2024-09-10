<?php

namespace MGModule\RealtimeRegisterSsl\eServices\provisioning;

use Exception;
use MGModule\RealtimeRegisterSsl\eServices\ScriptService;
use MGModule\RealtimeRegisterSsl\models\whmcs\product\Product;
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
    public const OPTION_ISSUED_SSL_MESSAGE = 'configoption23';
    public const OPTION_CUSTOM_GUIDE = 'configoption24';

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
        $product = new Product($_REQUEST['id']);
        return [
            'Product Identifier' => [
                'Type' => 'text'
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
            'Price Auto-Download' => [
                'Type' => 'yesno'
            ],
            'Included Wildcard SANs' => [
                'Type' => 'text'
            ],
            'z' => [
                'Type' => 'text',
                'Description' => self::buildConfigOptionsScript($product)
            ],
            'x' => [
                'Type' => 'text',
            ],
        ];
    }

    private static function buildConfigOptionsScript($product): string
    {
        $configOptions = $product->configuration()->getConfigOptions();
        $script = '<script>'
            . '$(function(){'
            . '$("input[name=\"packageconfigoption[1]\"]").prop("disabled", true);'
            . '$("input[name=\"packageconfigoption[3]\"]").prop("disabled", true);'
            . '$("input[name=\"packageconfigoption[7]\"]").parent("td.fieldarea").parent("tr").hide();'
            . '$("input[name=\"packageconfigoption[6]\"]").replaceWith($("input[name=\"packageconfigoption[8]\"]"));';

        if ($configOptions['configoption3'] !== 'on') {
            $script .= '$("input[name=\"packageconfigoption[4]\"]").prop("disabled", true);';
        }

        if ($configOptions['configoption13'] !== 'on') {
            $script .= '$("input[name=\"packageconfigoption[8]\"]").prop("disabled", true);';
        }
        
        $script .= '})</script>';
        return $script;
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
