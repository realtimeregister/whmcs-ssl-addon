<?php

use AddonModule\RealtimeRegisterSsl\Addon;
use AddonModule\RealtimeRegisterSsl\addonLibs\Lang;
use AddonModule\RealtimeRegisterSsl\eHelpers\Admin;
use AddonModule\RealtimeRegisterSsl\eHelpers\Invoice;
use AddonModule\RealtimeRegisterSsl\eHelpers\Whmcs;
use AddonModule\RealtimeRegisterSsl\eModels\whmcs\service\SSL;
use AddonModule\RealtimeRegisterSsl\eRepository\whmcs\config\Countries;
use AddonModule\RealtimeRegisterSsl\eServices\ConfigurableOptionService;
use AddonModule\RealtimeRegisterSsl\eServices\EmailTemplateService;
use AddonModule\RealtimeRegisterSsl\eServices\FlashService;
use AddonModule\RealtimeRegisterSsl\eServices\provisioning\Activator;
use AddonModule\RealtimeRegisterSsl\eServices\provisioning\ConfigOptions;
use AddonModule\RealtimeRegisterSsl\eServices\provisioning\SSLStepThree;
use AddonModule\RealtimeRegisterSsl\eServices\provisioning\SSLStepTwo;
use AddonModule\RealtimeRegisterSsl\eServices\provisioning\SSLStepTwoJS;
use AddonModule\RealtimeRegisterSsl\eServices\ScriptService;
use AddonModule\RealtimeRegisterSsl\eServices\TemplateService;
use AddonModule\RealtimeRegisterSsl\Loader;
use AddonModule\RealtimeRegisterSsl\models\logs\Repository as LogsRepo;
use AddonModule\RealtimeRegisterSsl\models\orders\Repository as OrderRepo;
use AddonModule\RealtimeRegisterSsl\models\productConfiguration\Repository;
use AddonModule\RealtimeRegisterSsl\models\whmcs\product\Product;
use AddonModule\RealtimeRegisterSsl\Server;
use Illuminate\Database\Capsule\Manager as Capsule;
use WHMCS\Service\Service;
use WHMCS\View\Formatter\Price;
use WHMCS\View\Menu\Item;

require_once __DIR__ . '/vendor/autoload.php';

if(!defined('DS'))define('DS',DIRECTORY_SEPARATOR);

add_hook("ClientAreaPage",1 ,function($vars) {
    if (empty($_SERVER['HTTP_REFERER']) || !str_contains($_SERVER['HTTP_REFERER'], 'clientsservices.php')) {
        return;
    }

    global $CONFIG;

    if (isset($_GET['id'])) {
        return true;
    }

    $urldata = parse_url($_SERVER['HTTP_REFERER']);
    parse_str($urldata['query'], $query);

    $serviceid = null;

    foreach($query as $key => $value) {
        unset($query[$key]);
        $query[str_replace('amp;', '', $key)] = $value;
    }

    if (strpos($urldata['path'], 'clientsservices.php') !== false) {
        if (isset($query['id']) && !empty($query['id'])) {
            $serviceid = $query['id'];
        }
        if (isset($query['productselect']) && !empty($query['productselect'])) {
            $serviceid = $query['productselect'];
        }
        if ($serviceid === null) {
            $service = Capsule::table('tblhosting')->select(['tblhosting.id as serviceid'])
                     ->join('tblproducts', 'tblproducts.id', '=', 'tblhosting.packageid')
                    ->where('tblhosting.userid', $query['userid'])
                    ->where('tblproducts.servertype', 'realtimeregister_ssl')
                    ->first();
            $serviceid = $service->serviceid;
        }

        $service = Capsule::table('tblhosting')->where('id', $serviceid)->first();

        if (isset($service->packageid) && !empty($service->packageid)) {
            $product = Capsule::table('tblproducts')->where('id', $service->packageid)
                ->where('servertype', 'realtimeregister_ssl')->first();

            if (isset($product->id)) {
                redir('action=productdetails&id='.$serviceid, 'clientarea.php');
            }
        }
    }
});

add_hook('ShoppingCartValidateProductUpdate', 1, function($params) {
    if ($params['csr']) {
        Server::I();
        $productId = $_SESSION['cart']['cartsummarypid'];
        $params['productId'] = $productId;
        $product = new Product($productId);
        $configOptions = $product->configuration()->getConfigOptions();
        $params['dcvmethodMainDomain'] = $params['dcv'];
        $params['fields']['sans_domains'] = $params['san'];
        $params['fields']['wildcard_san'] = $params['wildcardsan'];
        $params['phonenumber'] = $params['voice'];

        $sanConfigOptionId = ConfigurableOptionService::getForProduct($productId)[0]?->id ?? 0;
        $params['configoptions'][ConfigOptions::OPTION_SANS_COUNT]
            = $_SESSION['cart']['products'][$params['i']]['configoptions'][$sanConfigOptionId] ?? 0;

        $sanConfigOptionWildcardId = ConfigurableOptionService::getForProductWildcard($productId)[0]->id ?? 0;
        $params['configoptions'][ConfigOptions::OPTION_SANS_WILDCARD_COUNT]
            = $_SESSION['cart']['products'][$params['i']]['configoptions'][$sanConfigOptionWildcardId] ?? 0;

        $result = (new SSLStepTwo(array_merge($configOptions, $params)))->run();

        if ($result['error']) {
            return [$result['error']];
        }

        $_SESSION['cart']['products'][$params['i']]['domain'] = $result['commonName'];
    }

    return [];
});

add_hook('AfterShoppingCartCheckout', 1, function($params) {
    foreach ($params['ServiceIDs'] as $serviceId) {
        $service = Capsule::table('tblhosting')
            ->where('id', '=', $serviceId)
            ->first();
        $preFilledOrder = FlashService::getFieldsMemory($service->domain);
        if (!empty($preFilledOrder)) {
            $orderRepo = new OrderRepo();
            $orderRepo->addOrder(
                $service->userid,
                $serviceId,
                0,
                'EMAIL',
                'Pending Verification',
                FlashService::getFieldsMemory($service->domain)
            );
            FlashService::deleteFieldsMemory($service->domain);
        }
    }
});

add_hook('AfterModuleCreate', 999999999999, function ($params) {
    $orderRepo = new OrderRepo();
    $order = $orderRepo->getByServiceId($params['params']['serviceid']);
    if($order) {
        $logs = new LogsRepo();
        $orderParams = $params['params'];
        try {
            $orderData = json_decode($order->data, true);
            $orderParams['configdata']['fields']['sans_domains'] = $orderData['fields[sans_domains]'];
            foreach (explode(PHP_EOL, $orderData['fields[sans_domains]'] ?? '') as $sanDomain) {
                $orderParams['dcvmethod'][$sanDomain] = $orderData['dcvmethodMainDomain'];
            }
            $orderParams['configdata']['fields']['wildcard_san'] = $orderData['fields[wildcard_san]'];
            foreach (explode(PHP_EOL, $orderData['fields[wildcard_san]'] ?? '') as $wildcardSanDomain) {
                $orderParams['dcvmethod'][$wildcardSanDomain] = $orderData['dcvmethodMainDomain'];
            }
            $orderParams['noRedirect'] = true;
            $sslParams = array_merge($orderParams, $orderData);
            (new SSLStepThree($sslParams))->run();
        } catch (\Exception $e) {
            $logs->addLog($order->client_id, $order->service_id, 'error', $e->getMessage());
        }
    }
});

add_hook('ClientAreaPage', 1, function($params) {
    // List of valid template files that have additional processing
    $validTemplateFiles = ['configureproduct', 'configuressl-stepone'];

    // Check if the templatefile parameter exists and is in our list
    if (!isset($params['templatefile']) || ! in_array($params['templatefile'], $validTemplateFiles)) {
        return;
    }

    if (isset($params['templatefile'])) {
        // Load necessary classes only if a valid template file is confirmed
        new Loader();
        $activator = new Activator();
        $activator->run();

        global $smarty;
        switch ($params['templatefile']) {
            case 'configureproduct':
                $productId = $params['productinfo']['pid'];
                $product = Capsule::table('tblproducts')
                    ->where('id', '=', $productId)
                    ->first();

                $currentDomain = $_SESSION['cart']['products'][$_GET['i']]['domain'];
                $csrData = FlashService::parseSavedData($params['client'], $currentDomain);

                $csrModal = TemplateService::buildTemplate(ScriptService::CSR_MODAL, [
                    'csrData' => $csrData,
                    'countries' => Countries::getInstance()->getCountriesForAddonDropdown()
                ]);
                $csrModalScript = TemplateService::buildTemplate(ScriptService::GENERATE_CSR_MODAL, [
                    'fillVars' => addslashes(json_encode($csrData))
                ]);

                $preOrderVars = [
                    'sanOptionConfigId' => -1,
                    'includedSan' => -1,
                    'sanOptionWildcardConfigId' => -1,
                    'includedSanWildcard' => -1
                ];

                $sanOption = ConfigurableOptionService::getForProduct($productId)[0];
                if ($sanOption) {
                    $preOrderVars['sanOptionConfigId'] = $sanOption->id;
                    $preOrderVars['includedSan'] = $product->{ConfigOptions::PRODUCT_INCLUDED_SANS};
                }

                $sanOptionWildcard = ConfigurableOptionService::getForProductWildcard($productId)[0];
                if ($sanOptionWildcard) {
                    $preOrderVars['sanOptionWildcardConfigId'] = $sanOptionWildcard->id;
                    $preOrderVars['includedSanWildcard'] = $product->{ConfigOptions::PRODUCT_INCLUDED_SANS_WILDCARD};
                }

                $preOrderScript = TemplateService::buildTemplate(ScriptService::PRE_ORDER_FILL, $preOrderVars);

                $smarty->assign('sslOrderIntegrationCode', $preOrderScript. $csrModal . $csrModalScript);
                break;
            case 'configuressl-stepone':
                if (isset($_GET['cert'])) {
                    $r = \WHMCS\Database\Capsule::table('tblsslorders')->where(\WHMCS\Database\Capsule::raw(
                        'md5(id)'), '=', $_GET['cert']
                    )->first();
                    if ($r?->module == Server::I()->configuration()->systemName) {
                        $smarty->assign('customBackToServiceButton', true);
                        $smarty->assign('customBackToServiceButtonLang', Lang::T('addonCA', 'customBackToServiceButtonLang'));
                    }
                }
                break;
        }
    }
});

add_hook('ClientAreaHeadOutput', 1, function($params)
{
    if ($params['clientareaaction'] == 'services') {
          $services = Capsule::table('tblhosting')
                ->select(['tblhosting.id'])
                ->join('tblproducts', 'tblproducts.id','=', 'tblhosting.packageid')
                ->join('tblsslorders', 'tblsslorders.serviceid','=', 'tblhosting.id')
                ->where('tblhosting.userid', $_SESSION['uid'])
                ->where('tblsslorders.status', SSL::AWAITING_CONFIGURATION)
                ->where('tblproducts.servertype', 'realtimeregister_ssl')
               ->get();

        $awaitingServicesREALTIMEREGISTERSSL = [];
        foreach($services as $service) {
            $awaitingServicesREALTIMEREGISTERSSL[$service->id] = $service->id;
        }

        return '<script type="text/javascript">
        $(document).ready(function () {
        
            var awaitingServicesREALTIMEREGISTERSSL = '. json_encode($awaitingServicesREALTIMEREGISTERSSL). ';

            $("#tableServicesList tbody tr").each(function(index) {
                var serviceid = $(this).find("td:first-child").attr("data-element-id");
                
                if(awaitingServicesREALTIMEREGISTERSSL[serviceid])
                {
                    $(this).find("td:nth-child(2)").append("<br><span class=\"label label-warning\">Awaiting Configuration</span>")
                }

            });
        });
    </script>';
    }

    $show = false;

    if (
        $params['filename'] === 'index'
    ) {
        if($_REQUEST['action'] === 'generateCsr') {
            $GenerateCsr = new AddonModule\RealtimeRegisterSsl\eServices\provisioning\GenerateCSR($params, $_POST);
            echo $GenerateCsr->run();
            die();
        }

        if($_REQUEST['action'] === 'approverEmails') {
            $approverEmails = (new SSLStepTwoJS($params))
                ->fetchApprovalEmailsForSansDomains([$_REQUEST['commonName']]);
            echo json_encode(['success' => true, 'emails' => $approverEmails]);
            die();
        }

    }


    if ($params['templatefile'] === 'clientareacancelrequest') {
        try {
            $service = Service::findOrFail($params['id']);
            if ($service->product->servertype === 'realtimeregister_ssl') {
                $show = true;
            }
        }
        catch (Exception $exc) {
        }
    } elseif ($params['modulename'] === 'realtimeregister_ssl') {
        $show = true;
    }
    if (!$show) {
        return '';
    }

    $url = $_SERVER['PHP_SELF'] . '?action=productdetails&id=' . $_GET['id'];

    return '<script type="text/javascript">
        $(document).ready(function () {
            var information = $("#Primary_Sidebar-Service_Details_Overview-Information"),
                    href = information.attr("href");
            if (typeof href === "string") {
                information.attr("href", "' . $url . '");
                information.removeAttr("data-toggle");
            }
        });
    </script>';
});
add_hook('ClientLogin', 1, function($vars)
{
    if (isset($_REQUEST['redirectToProductDetails'], $_REQUEST['serviceID'])
        && $_REQUEST['redirectToProductDetails'] === 'true' && is_numeric($_REQUEST['serviceID'])) {
        $ca = new WHMCS_ClientArea();
        if ($ca->isLoggedIn()) {
            header('Location: clientarea.php?action=productdetails&id=' . $_REQUEST['serviceID']);
            die();
        }
    }
});

add_hook('InvoicePaid', 1, function($vars)
{
    require_once dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'init.php';
    require_once __DIR__ . DS . 'Loader.php';

    new Loader();
    Addon::I(true);

    $invoiceGenerator = new Invoice();

    $invoiceInfo = $invoiceGenerator->getInvoiceCreatedInfo($vars['invoiceid']);
    if (!empty($invoiceInfo)) {
        $command = 'SendEmail';
        $postData = [
            'id'          => $invoiceInfo['service_id'],
            'messagename' => EmailTemplateService::RENEWAL_TEMPLATE_ID
        ];
        $adminUserName = Admin::getAdminUserName();
        $results = localAPI($command, $postData, $adminUserName);
        $resultSuccess = $results['result'] == 'success';
        if (!$resultSuccess)
        {
            Whmcs::savelogActivityRealtimeRegisterSsl(
                'Realtime Register SSL WHMCS Notifier: Error while sending customer notifications (service '
                . $invoiceInfo['service_id'] . '): ' . $results['message']
            );
        }
    }

    return true;
});


/*
 *
 * assign ssl summary stats to client area page
 * 
 */

function realtimeregister_ssl_displaySSLSummaryStats($vars)
{
    if (
        isset($vars['filename'], $vars['templatefile']) && $vars['filename'] == 'clientarea'
        && $vars['templatefile'] == 'clientareahome'
    ) {
        try {
            require_once __DIR__ . DS . 'Loader.php';
            new Loader();

            GLOBAl $smarty;

            Addon::I(true);

            $apiConf           = (new \AddonModule\RealtimeRegisterSsl\models\apiConfiguration\Repository())->get();
            $displaySSLSummary = $apiConf->display_ca_summary;
            if (!$displaySSLSummary) {
                return '';
            }

            $titleLang       = Lang::T('addonCA', 'sslSummary', 'title');
            $unpaidLang      = Lang::T('addonCA', 'sslSummary', 'unpaid');
            $processingLang  = Lang::T('addonCA', 'sslSummary', 'processing');
            $expiresSoonLang = Lang::T('addonCA', 'sslSummary', 'expiresSoon');
            $viewAll         = Lang::T('viewAll');

            //get ssl statistics
            $sslSummaryStats = new AddonModule\RealtimeRegisterSsl\eHelpers\SSLSummary($_SESSION['uid']);

            $totalOrders = $sslSummaryStats->getTotalSSLOrdersCount();

            if ((int) $totalOrders == 0) {
                return '';
            }

            $unpaidOrders      = $sslSummaryStats->getUnpaidSSLOrdersCount();
            $processingOrders  = $sslSummaryStats->getProcessingSSLOrdersCount();
            $expiresSoonOrders = $sslSummaryStats->getExpiresSoonSSLOrdersCount();

            $sslSummaryIntegrationCode = "
                <div class=\"col-sm-12\">
                        <div menuitemname=\"SSL Order Summary\" class=\"panel panel-default panel-accent-gold\">
                                <div class=\"panel-heading\">
                                        <h3 class=\"panel-title\">
                                                <div class=\"pull-right\">
                                                        <a class=\"btn btn-default bg-color-gold btn-xs\"
                                                                href=\"index.php?m=realtimeregister_ssl&addon-page=Orders&type=total\">
                                                                <i class=\"fas fa-plus\"></i>
                                                                $viewAll
                                                        </a>
                                                </div>
                                                <i class=\"fas fa-lock\"></i>
                                                $titleLang
                                        </h3>
                                </div>
                                <div>
                                        <div class=\"dsb-box col-sm-4\">
                                                <a href=\"index.php?m=realtimeregister_ssl&addon-page=Orders&type=unpaid\">
                                                        <div><i class=\"fa fa-credit-card icon icon col-sm-12\"></i><span>$unpaidLang<u>$unpaidOrders</u></span></div>
                                                </a>
                                        </div>
                                        <div class=\"dsb-box col-sm-4\">
                                                <a href=\"index.php?m=realtimeregister_ssl&addon-page=Orders&type=processing\">
                                                        <div><i class=\"fa fa-cogs icon col-sm-12\"></i><span>$processingLang<u>$processingOrders</u></span></div>
                                                </a>
                                        </div>
                                        <div class=\"dsb-box col-sm-4\">
                                                <a href=\"index.php?m=realtimeregister_ssl&addon-page=Orders&type=expires_soon\">
                                                        <div><i class=\"fa fa-hourglass-half icon col-sm-12\"></i><span>$expiresSoonLang<u>$expiresSoonOrders</u></span></div>
                                                </a>
                                        </div>
                                </div>
                        </div>
                </div>";




            $smarty->assign('sslSummaryIntegrationCode', $sslSummaryIntegrationCode);

            global $smartyvalues;
            $smartyvalues['sslSummaryIntegrationCode'] = $sslSummaryIntegrationCode;
        }
        catch (Exception $e)
        {
        }
    }
    return '';
}

add_hook('ClientAreaPageHome', 1, 'realtimeregister_ssl_displaySSLSummaryStats');

function realtimeregister_ssl_loadSSLSummaryCSSStyle($vars)
{
    if (isset($vars['filename'], $vars['templatefile']) && $vars['filename'] == 'clientarea'
        && $vars['templatefile'] == 'clientareahome'
    ) {
        return <<<HTML
    <link href="./modules/addons/realtimeregister_ssl/templates/clientarea/default/assets/css/sslSummary.css" rel="stylesheet" type="text/css" />
HTML;
    }
}
add_hook('ClientAreaHeadOutput', 1, 'realtimeregister_ssl_loadSSLSummaryCSSStyle');

function realtimeregister_ssl_displaySSLSummaryInSidebar($secondarySidebar)
{
    GLOBAL $smarty;

    try
    {
        require_once __DIR__ . DS . 'Loader.php';
        new Loader();

        Addon::I(true);

        $apiConf           = (new \AddonModule\RealtimeRegisterSsl\models\apiConfiguration\Repository())->get();

        if(!isset($apiConf->sidebar_templates) || empty($apiConf->sidebar_templates)) {
            if (in_array($smarty->tpl_vars['templatefile']->value, ['clientareahome']) || !isset($_SESSION['uid'])) {
                return;
            }
        } else {
            if (!in_array(
                $smarty->tpl_vars['templatefile']->value,
                explode(',', $apiConf->sidebar_templates)) || !isset($_SESSION['uid'])
            ) {
                return;
            }
        }

        $displaySSLSummary = $apiConf->display_ca_summary;
        if (!(bool) $displaySSLSummary) {
            return;
        }

        //get ssl statistics
        $sslSummaryStats = new AddonModule\RealtimeRegisterSsl\eHelpers\SSLSummary($_SESSION['uid']);

        $totalOrders       = $sslSummaryStats->getTotalSSLOrdersCount();
        if ((int) $totalOrders == 0) {
            return '';
        }
        $unpaidOrders      = $sslSummaryStats->getUnpaidSSLOrdersCount();
        $processingOrders  = $sslSummaryStats->getProcessingSSLOrdersCount();
        $expiresSoonOrders = $sslSummaryStats->getExpiresSoonSSLOrdersCount();

        /** @var Item $secondarySidebar */
        $newMenu = $secondarySidebar->addChild(
                'uniqueMenuSLLSummaryName', [
            'name'  => 'Home',
            'label' => Lang::getInstance()->absoluteT('addonCA', 'sslSummary', 'title'),
            'uri'   => '',
            'order' => 99,
            'icon'  => '',
            ]
        );
        $newMenu->addChild(
                'uniqueSubMenuSLLSummaryTotal', [
            'name'  => 'totalOrders',
            'label' => Lang::getInstance()->absoluteT('addonCA', 'sslSummary', 'total'),
            'uri'   => 'index.php?m=realtimeregister_ssl&addon-page=Orders&type=total',
            'order' => 10,
            'badge' => $totalOrders,
            ]
        );
        $newMenu->addChild(
                'uniqueSubMenuSLLSummaryUnpaid', [
            'name'  => 'unpaidOrders',
            'label' => Lang::getInstance()->absoluteT('addonCA', 'sslSummary', 'unpaid'),
            'uri'   => 'index.php?m=realtimeregister_ssl&addon-page=Orders&type=unpaid',
            'order' => 11,
            'badge' => $unpaidOrders,
            ]
        );
        $newMenu->addChild(
                'uniqueSubMenuSLLSummaryProcessing', [
            'name'  => 'processingOrders',
            'label' => Lang::getInstance()->absoluteT('addonCA', 'sslSummary', 'processing'),
            'uri'   => 'index.php?m=realtimeregister_ssl&addon-page=Orders&type=processing',
            'order' => 12,
            'badge' => $processingOrders,
            ]
        );
        $newMenu->addChild(
                'uniqueSubMenuSLLSummaryExpires', [
            'name'  => 'expiresSoonOrders',
            'label' => Lang::absoluteT('addonCA', 'sslSummary', 'expiresSoon'),
            'uri'   => 'index.php?m=realtimeregister_ssl&addon-page=Orders&type=expires_soon',
            'order' => 13,
            'badge' => $expiresSoonOrders,
            ]
        );
    }
    catch (Exception $e)
    {

    }
}
add_hook('ClientAreaSecondarySidebar', 1, 'realtimeregister_ssl_displaySSLSummaryInSidebar');

function realtimeregister_ssl_overideProductPricingBasedOnDiscount($vars)
{
    require_once __DIR__ . DS . 'Loader.php';
    new Loader();
    AddonModule\RealtimeRegisterSsl\Addon::I(true);
    //load module products
    $products     = [];
    $productModel = new Repository();
    $properties = ["msetupfee", "asetupfee", "bsetupfee", "tsetupfee", "monthly", "annually", "biennially", "triennially"];

    if(isset($_SESSION['uid']) && !empty($_SESSION['uid'])) {
        $clientCurrency = getCurrency($_SESSION['uid'])['id'];
    } else {
        $currency = Capsule::table('tblcurrencies')->where('default', '1')->first();
        $clientCurrency['id'] = isset($_SESSION['currency']) && !empty($_SESSION['currency']) ? $_SESSION['currency']
            : $currency->id;
    }
    // get Realtime Register Ssl all products
    foreach ($productModel->getModuleProducts() as $product) {
        if($product->servertype != 'realtimeregister_ssl') {
            continue;
        }

        if ($product->id == $vars['pid']) {
            $percentage = AddonModule\RealtimeRegisterSsl\eHelpers\Discount::getDiscountValue($vars);
            if (!$percentage) {
                return [];
            }

            $configoptions = $vars['proddata']['configoptions'];
            $discount = 0;

            foreach ($configoptions as $optionId => $value) {
                $option = ConfigurableOptionService::getConfigOptionById($optionId);
                if (str_contains($option->optionname, 'sans')) {
                    $optionSub = ConfigurableOptionService::getConfigOptionSubByOptionId($optionId);
                    $pricing = Capsule::table("tblpricing")
                        ->where("relid", "=", $optionSub->id)
                        ->where("currency", "=", $clientCurrency)
                        ->first();
                    $quantity = $value;
                } else {
                    $pricing = Capsule::table("tblpricing")
                        ->where("relid", "=", $value)
                        ->where("currency", "=", $clientCurrency)
                        ->first();
                    $quantity = 1;
                }
                foreach($properties as $property) {
                    $discount -= floatval($pricing->{$property}) * $quantity;
                }
            }

            if ($discount) {
                return ['recurring' => $discount / 100 * $percentage];
            }
        }
    }

    return [];
}

add_hook('OrderProductPricingOverride', 1, 'realtimeregister_ssl_overideProductPricingBasedOnDiscount');


add_hook('InvoiceCreation', 1, function($vars) {
    $invoiceid = $vars['invoiceid'];

    $items = Capsule::table('tblinvoiceitems')->where('invoiceid', $invoiceid)->where('type', 'Upgrade')->get();

    foreach ($items as $item) {
        $description = $item->description;

        $upgradeid = $item->relid;
        $upgrade = Capsule::table('tblupgrades')->where('id', $upgradeid)->first();

        $serviceid = $upgrade->relid;
        $service = Capsule::table('tblhosting')->where('id', $serviceid)->first();

        $productid = $service->packageid;
        $product = Capsule::table('tblproducts')->where('id', $productid)
            ->where('paytype', 'onetime')->where('servertype', 'realtimeregister_ssl')->first();

        if (isset($product->configoption7) && !empty($product->configoption7)) {
            if (strpos($description, '00/00/0000') !== false) {
                $description = str_replace('- 00/00/0000', '', $description);
                $length = strlen($description);
                $description = substr($description, 0, $length-13);

                Capsule::table('tblinvoiceitems')->where('id', $item->id)->update(
                    ['description' => trim($description)]
                );
            }
        }
    }
});

add_hook('ClientAreaHeadOutput', 1, function($vars) {
    return <<<HTML
    <style>
    .hidden {
        display:none;
    }
    </style>
HTML;

});

add_hook('AdminAreaFooterOutput', 1, function($vars)
{
    if ($vars['filename'] == 'clientsservices' && $_GET['userid'] && $_GET['id']) {
        $hosting = \WHMCS\Database\Capsule::table('tblhosting')
            ->join('tblproducts', 'tblproducts.id', '=', 'tblhosting.packageid')
            ->where('tblhosting.id', $_GET['id'])
            ->first();
        if($hosting->servertype != 'realtimeregister_ssl') {
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

add_hook('ClientAreaHeadOutput', 2, function($vars) {
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

add_hook('DailyCronJob', 10, function () {
    global $CONFIG;

    require_once __DIR__ . DS . 'Loader.php';
    new Loader();

    $apiConfiguration = (new AddonModule\RealtimeRegisterSsl\models\apiConfiguration\Repository())->get();

    $config = AddonModule\RealtimeRegisterSsl\Addon::config();

    $information =  [
        'servername' => preg_replace('#^http(s)?://#', '', rtrim($CONFIG['SystemURL'], '/')),
        'php' => phpversion(),
        'whmcsversion' => $CONFIG['Version'],
        'module_version' => $config['version'],
        'default_country' => $CONFIG['DefaultCountry'],
        'default_language' => $CONFIG['Language'],
        'ote' => $apiConfiguration->api_test ? 'true' : 'false'
    ];

    if (strlen($apiConfiguration->api_login) > 0) {
        $information['handle'] = explode('/', base64_decode($apiConfiguration->api_login))[0];
    }

    $url = 'https://realtimeregister.com/whmcs-update/realtimeregister_ssl/version';

    if (!empty($information['handle'])) {
        $url .= '?' . http_build_query($information);
    }

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    curl_close($ch);
});
