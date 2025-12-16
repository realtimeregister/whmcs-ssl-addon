<?php

use WHMCS\Database\Capsule as DB;

if(!defined('DS')) {
    define('DS',DIRECTORY_SEPARATOR);
}

add_hook('ClientAreaPageUpgrade', 1, function($vars)
{
    $step = filter_input(INPUT_POST, 'step', FILTER_VALIDATE_INT);

    if ($vars['type'] == 'configoptions' && $step == 2) {
        $promodata = validateUpgradePromo($vars['promocode']);

        //calculate the percentage value
        $serviceData = DB::table('tblhosting')
            ->join('tblproducts', 'tblhosting.packageid', '=', 'tblproducts.id')
            ->select(
                'tblhosting.id',
                'tblhosting.packageid',
                'tblhosting.domain',
                'tblhosting.nextduedate',
                'tblhosting.billingcycle',
                'tblproducts.servertype'
            )
            ->where('tblhosting.userid', '=', $vars['clientsdetails']['userid'])
            ->where('tblhosting.id', '=', $vars['id'])
            ->first();

        if ($serviceData->servertype == 'realtimeregister_ssl') {
            $nextduedate  = $serviceData->nextduedate;
            $billingcycle = $serviceData->billingcycle;

            $year            = substr($nextduedate, 0, 4);
            $month           = substr($nextduedate, 5, 2);
            $day             = substr($nextduedate, 8, 2);
            $cyclemonths     = getBillingCycleMonths($billingcycle);
            $prevduedate     = date("Y-m-d", mktime(0, 0, 0, $month - $cyclemonths, $day, $year));
            $totaldays       = round((strtotime($nextduedate) - strtotime($prevduedate)) / 86400);
            $todaysdate      = date("Ymd");
            $todaysdate      = strtotime($todaysdate);
            $nextduedatetime = strtotime($nextduedate);
            $days            = round(($nextduedatetime - $todaysdate) / 86400);

            if( $days < 0 ) {
                $days = $totaldays;
            }

            $percentage       = $days / $totaldays;
            $upgrades         = $vars['upgrades'];
            $newUpgrades      = [];
            $subtotal         = 0;
            $configoptions    = getCartConfigOptions(
                $serviceData->packageid,
                $vars['configoptions'],
                $serviceData->billingcycle
            );
            $oldconfigoptions = getCartConfigOptions(
                $serviceData->packageid,
                "",
                $billingcycle,
                $serviceData->id
            );
            $discount = 0;

            foreach ($upgrades as $upgrade) {
                foreach ($configoptions as $key2 => $configoption) {
                    if ($configoption['optionname'] == $upgrade['configname']) {
                        $testPrice = $configoptions[$key2]['selectedrecurring']
                            - $oldconfigoptions[$key2]['selectedrecurring'];
                        $priceWithPercentage = $testPrice * $percentage;
                        $configid            = $configoption['id'];
                    }
                }

                $newPrice = floatval($priceWithPercentage) / floatval($percentage);
                $subtotal += number_format($newPrice, 2);

                if (
                    $GLOBALS["qualifies"] && 0 < $newPrice && (!count($promodata['configoptions'])
                        || in_array($configid, $promodata['configoptions']))
                ) {
                    $itemdiscount = ($promodata["discounttype"] == "Percentage"
                        ? round($newPrice * ($promodata["value"] / 100), 2)
                        : ($newPrice < $promodata["value"] ? $newPrice : $promodata["value"]));
                    $discount += $itemdiscount;
                }

                $upgrade['price'] = formatCurrency($newPrice);
                $newUpgrades[]    = $upgrade;
            }

            $newSubtotal       = formatCurrency($subtotal - $discount);
            $subtotalToCalcTax = $newSubtotal->toNumeric();

            if ($vars['taxenabled']) {
                global $CONFIG;

                if ($vars['taxrate']) {
                    if ($CONFIG["TaxType"] == "Inclusive") {
                        $inctaxrate = 1 + $vars['taxrate'] / 100;
                        $tempsubtotal = $subtotalToCalcTax;
                        $subtotalToCalcTax = $subtotalToCalcTax / $inctaxrate;
                        $tax = $tempsubtotal - $subtotalToCalcTax;
                    } else {
                        $tax = $subtotalToCalcTax * $vars['taxrate'] / 100;
                    }
                }

                if ($vars['taxrate2']) {
                    $tempsubtotal = $subtotalToCalcTax;
                    if ($CONFIG["TaxL2Compound"]) {
                        $tempsubtotal += $tax;
                    }

                    if ($CONFIG["TaxType"] == "Inclusive") {
                        //var_dump($tempsubtotal);
                        $inctaxrate = 1 + $vars['taxrate'] / 100;
                        $subtotalToCalcTax = $tempsubtotal / $inctaxrate;
                        $tax2 = $tempsubtotal - $subtotalToCalcTax;
                    } else {
                        $tax2 = $tempsubtotal * $vars['taxrate2'] / 100;
                    }
                }

                $tax = format_as_currency(round($tax, 2));
                $tax2 = format_as_currency(round($tax2, 2));
                $newSubtotal = formatCurrency($subtotalToCalcTax);
            }

            $newTotal = formatCurrency($newSubtotal->toNumeric() + $tax + $tax2);

            return [
                'upgrades' => $newUpgrades,
                'subtotal' => $newSubtotal,
                'total' => $newTotal,
                'tax' => formatCurrency($tax),
                'tax2' => formatCurrency($tax2),
                'discount' => formatCurrency($discount)
            ];
        }
    }
});

add_hook('AdminAreaFooterOutput', 1, function($vars)
{
if ($vars['filename'] == 'clientsservices' && $_GET['userid'] && $_GET['id']) {
    $hosting = DB::table('tblhosting')
    ->join('tblproducts', 'tblproducts.id', '=', 'tblhosting.packageid')
    ->where('tblhosting.id', $_GET['id'])
    ->first();
    if ($hosting->servertype != 'realtimeregister_ssl') {
        return;
    }
    return <<<JS

<script>
$(function(){
    $('#btnCreate').hide();
    
    var statushtrml = $('select[name="domainstatus"]').parent().html();
    var statustext = $('select[name="domainstatus"]').parent('.fieldarea').prev().text();

    $('#inputDedicatedip').parent('.fieldarea').prev().hide();
    $('#inputDedicatedip').parent('.fieldarea').hide();
    $('#inputUsername').parent('.fieldarea').prev().hide();
    $('#inputUsername').parent('.fieldarea').hide();
    $('#inputPassword').parent('.fieldarea').prev().hide();
    $('#inputPassword').parent('.fieldarea').hide();
    
    
    $('select[name="domainstatus"]').parent('.fieldarea').prev().hide();
    $('select[name="domainstatus"]').parent('.fieldarea').hide();
    $('select[name="domainstatus"]').remove();
    
    $('select[name="server"]').parent('.fieldarea').prev().html(statustext);
    $('select[name="server"]').parent('.fieldarea').html(statushtrml);
});
</script>

JS;
}
});

add_hook('ClientAreaHeadOutput', 1, function($vars) {
    if($vars['template'] == 'twenty-one' && $vars['module'] == 'realtimeregister_ssl') {
        return <<<HTML
<script type="text/javascript">
$(document).ready(function (){
    $('.modal-header.panel-heading').css({'display':'block'});
});
</script>
HTML;

    }
});
