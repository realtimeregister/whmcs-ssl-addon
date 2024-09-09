<?php

use WHMCS\Application;
use WHMCS\Config\Setting;
use WHMCS\Exception\ProgramExit;
use WHMCS\Product\Product;
use WHMCS\Session;
use WHMCS\User\Client;

require "../init.php";

/*
*** USAGE SAMPLES ***

<script language="javascript" src="feeds/productsinfo.php?pid=1&get=name"></script>

<script language="javascript" src="feeds/productsinfo.php?pid=1&get=description"></script>

<script language="javascript" src="feeds/productsinfo.php?pid=1&get=price&billingcycle=monthly&currency=1"></script>

<script language="javascript" src="feeds/productsinfo.php?pid=1&get=orderurl&carttpl=web20cart"></script>

*/

$whmcs = App::self();
$pid = (int) $whmcs->get_req_var('pid');
$get = $whmcs->get_req_var('get');
$language = $whmcs->get_req_var('language') ?: null;
$data = [];
$name = $description = '';

// Verify user input for pid exists, is greater than 0, and as is a valid id
if ($pid > 0) {
    $result = select_query("tblproducts", "", ["id" => $pid]);
    $data = mysqli_fetch_array($result);

    $pid = (int) $data['id'];
    // If there is a user logged in, we will use the client language
    if (((int) $userId = Session::get('userid'))) {
        $language = Client::find($userId, ['language'])->language ?: null;
    }
    $name = Product::getProductName($pid, $data['name'], $language);
    $description = Product::getProductDescription($pid, $data['description'], $language);
}

// Verify that the pid is not less than 1 to in order to continue.
if ($pid < 1) {
    widgetOutput('Product ID Not Found');
}

if ($get=="name") {
    widgetOutput($name);
} elseif ($get=="description") {
    $description = str_replace(["\r", "\n", "\r\n"], "", nl2br($description));
    widgetOutput($description);
} elseif ($get=="configoption") {
    $configOptionNum = $whmcs->get_req_var('configoptionnum');
    if (!$configOptionNum) {
        widgetOutput('The variable configoptionnum is required when get is configoption.');
    }
    widgetoutput($data['configoption' . (int) $configOptionNum]);
} elseif ($get=="orderurl") {
    $cartTemplate = $whmcs->get_req_var('carttpl');
    if ($cartTemplate == "ajax") {
        $cartTemplate = "ajaxcart";
    }
    $systemUrl = App::getSystemUrl();
    if (!$cartTemplate) {
        $cartTemplate = Setting::getValue('OrderFormTemplate ');
    }
    widgetOutput("{$systemUrl}cart.php?a=add&pid={$pid}&carttpl={$cartTemplate}");
} elseif ($get=="price") {
    // Verify user input for currency exists, is numeric, and as is a valid id
    $billingCycle = $whmcs->get_req_var('billingcycle');

    $currencyID = $whmcs->get_req_var('currency');
    if (!is_numeric($currencyID)) {
        $currency = array();
    } else {
        $currency = getCurrency('', $currencyID);
    }

    if (!$currency || !is_array($currency) || !isset($currency['id'])) {
        $currency = getCurrency();
    }
    $currencyID = $currency['id'];
    if ($data['servertype'] == 'realtimeregister_ssl') {
        require_once ROOTDIR.'/modules/addons/realtimeregister_ssl/Loader.php';
        new \MGModule\RealtimeRegisterSsl\Loader();
        MGModule\RealtimeRegisterSsl\Addon::I(true);
        $commission = MGModule\RealtimeRegisterSsl\eHelpers\Discount::getDiscountValue(['pid' => $pid]);
        $price = MGModule\RealtimeRegisterSsl\eHelpers\Whmcs::getPricingInfo($pid, $commission);

        if(!$billingCycle || !isset($price['cycles'][$billingCycle]))
        {
            if (!function_exists('array_key_first')) {
                function array_key_first(array $arr) {
                    foreach($arr as $key => $unused) {
                        return $key;
                    }
                    return NULL;
                }
            }
            $billingCycle = array_key_first($price['cycles']);
        }
        widgetOutput($price['cycles'][$billingCycle]);

    }
    $result = select_query("tblpricing", "", ["type" => "product", "currency" => $currencyID, "relid" => $pid]);
    $data = mysqli_fetch_array($result);
    $price = $data[$billingCycle];
    $price = formatCurrency($price);
    widgetOutput($price);
} else {
    widgetOutput('Invalid get option. Valid options are "name", "description", "configoption", "orderurl" or "price"');
}

/**
 * The function to output the widget data to the browser in a javascript format.
 *
 * @throws WHMCS\Exception\ProgramExit
 * @param string $value the data to output
 */
function widgetOutput($value) {
    echo "document.write('".addslashes($value)."');";
    throw new ProgramExit();
}
