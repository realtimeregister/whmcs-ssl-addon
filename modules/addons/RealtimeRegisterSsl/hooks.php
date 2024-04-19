<?php
use Illuminate\Database\Capsule\Manager as Capsule;

require_once __DIR__ . '/vendor/autoload.php';

if(!defined('DS'))define('DS',DIRECTORY_SEPARATOR);

add_hook("ClientAreaPage",1 ,function($vars) {

    global $CONFIG;

    if(substr($CONFIG['Version'],0,1) == '8')
    {
        if(isset($_GET['id'])) return true;

        $urldata = parse_url($_SERVER['HTTP_REFERER']);
        parse_str($urldata['query'], $query);

        $serviceid = null;

        foreach($query as $key => $value)
        {
            unset($query[$key]);
            $query[str_replace('amp;', '', $key)] = $value;
        }

        if (strpos($urldata['path'], 'clientsservices.php') !== false) {

            if(isset($query['id']) && !empty($query['id']))
            {
                $serviceid = $query['id'];
            }
            if(isset($query['productselect']) && !empty($query['productselect']))
            {
                $serviceid = $query['productselect'];
            }
            if($serviceid === null)
            {
                $service = Capsule::table('tblhosting')->select(['tblhosting.id as serviceid'])
                         ->join('tblproducts', 'tblproducts.id', '=', 'tblhosting.packageid')
                        ->where('tblhosting.userid', $query['userid'])
                        ->where('tblproducts.servertype', 'RealtimeRegisterSsl')
                        ->first();
                $serviceid = $service->serviceid;
            }

            $service = Capsule::table('tblhosting')->where('id', $serviceid)->first();

            if(isset($service->packageid) && !empty($service->packageid))
            {
                $product = Capsule::table('tblproducts')->where('id', $service->packageid)->where('servertype', 'RealtimeRegisterSsl')->first();

                if(isset($product->id))
                {
                    redir('action=productdetails&id='.$serviceid, 'clientarea.php');
                }
            }
        }
    }
});


add_hook('ClientAreaPage', 1, function($params) {

    if($params['templatefile'] != 'invoice-payment' && $params['filename'] != 'viewinvoice')
    {
        \MGModule\RealtimeRegisterSsl\eHelpers\Invoice::createPendingPaymentInvoice();
        $checkInvoicePending = Capsule::table(\MGModule\RealtimeRegisterSsl\eHelpers\Invoice::INVOICE_PENDINGPAYMENT_TABLE_NAME)->where('user_id', $_SESSION['uid'])->get();
        foreach($checkInvoicePending as $invoiceToPending)
        {
            $checkUnpaid = Capsule::table('tblinvoices')->where('id', $invoiceToPending->invoice_id)->where('status', 'Unpaid')->first();
            if(isset($checkUnpaid->id)) {
                Capsule::table('tblinvoices')->where('id', $invoiceToPending->invoice_id)->update(['status' => 'Payment Pending']);
                Capsule::table(\MGModule\RealtimeRegisterSsl\eHelpers\Invoice::INVOICE_PENDINGPAYMENT_TABLE_NAME)->where('invoice_id', $invoiceToPending->invoice_id)->delete();
            }
        }
    }

    if (
        $params['filename'] == 'viewinvoice' && ($params['status'] == 'Payment Pending'
            ||  $params['status'] == 'Unpaid')
    ) {
        Capsule::table('tblinvoices')->where('id', $params['invoiceid'])->update([
            'status' => 'Unpaid'
        ]);

        \MGModule\RealtimeRegisterSsl\eHelpers\Invoice::createPendingPaymentInvoice();
        $check = Capsule::table(
            'mgfw_REALTIMEREGISTERSSL_invoices_pendingpayment'
        )->where('user_id', $_SESSION['uid'])->where('invoice_id', $params['invoiceid'])->first();
        if (!isset($check->id))
        {
            Capsule::table(\MGModule\RealtimeRegisterSsl\eHelpers\Invoice::INVOICE_PENDINGPAYMENT_TABLE_NAME)->insert([
                'user_id' => $_SESSION['uid'],
                'invoice_id' => $params['invoiceid'],
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

//        redir('id='.$params['invoiceid'], 'viewinvoice.php');
    }

    new \MGModule\RealtimeRegisterSsl\Loader();
    $activator = new \MGModule\RealtimeRegisterSsl\eServices\provisioning\Activator();
    $activator->run();

    if (isset($params['templatefile']))
    {
        global $smarty;
        switch ($params['templatefile'])
        {
            case 'configureproduct':
                $product = \WHMCS\Database\Capsule::table('tblproducts')->where('id', $params['productinfo']['pid'])->where('servertype', 'RealtimeRegisterSsl')->first();
                $includedsan = $product->configoption4;
                $includedsanwildcard = $product->configoption8;

                $txtincluded = '';

                if($includedsan > 0)
                {
                    $txt = sprintf (\MGModule\RealtimeRegisterSsl\mgLibs\Lang::getInstance()->T('additionalSingleDomainInfo'), $includedsan);
                    $txtincluded .= '<p>'.$txt.'</p>';
                }
                if($includedsanwildcard > 0)
                {
                    $txt = sprintf (\MGModule\RealtimeRegisterSsl\mgLibs\Lang::getInstance()->T('additionalSingleDomainWildcardInfo'), $includedsanwildcard);
                    $txtincluded .= '<p>'.$txt.'</p>';
                }
                $smarty->assign('txtincluded', $txtincluded);
                break;
            case 'configuressl-stepone':
                if (isset($_GET['cert']))
                {
                    $r = \WHMCS\Database\Capsule::table('tblsslorders')->where(\WHMCS\Database\Capsule::raw('md5(id)'), '=', $_GET['cert'])->first();
                    if ($r && $r->module == \MGModule\RealtimeRegisterSsl\Server::I()->configuration()->systemName)
                    {
                        $smarty->assign('customBackToServiceButton', true);
                        $smarty->assign('customBackToServiceButtonLang', \MGModule\RealtimeRegisterSsl\mgLibs\Lang::T('addonCA', 'customBackToServiceButtonLang'));
                    }
                }
                break;
        }
    }
});

add_hook('ClientAreaHeadOutput', 1, function($params)
{
    if($params['clientareaaction'] == 'services')
    {
          $services = Capsule::table('tblhosting')
                ->select(['tblhosting.id'])
                ->join('tblproducts', 'tblproducts.id','=', 'tblhosting.packageid')
                ->join('tblsslorders', 'tblsslorders.serviceid','=', 'tblhosting.id')
                ->where('tblhosting.userid', $_SESSION['uid'])
                ->where('tblsslorders.status', 'Awaiting Configuration')
                ->where('tblproducts.servertype', 'RealtimeRegisterSsl')
               ->get();
        
        $awaitingServicesREALTIMEREGISTERSSL = [];
        foreach($services as $service)
        {
            $awaitingServicesREALTIMEREGISTERSSL[$service->id] = $service->id;
        }
        
        
        
        return '<script type="text/javascript">
        $(document).ready(function () {
        
            var awaitingServicesREALTIMEREGISTERSSL = '. json_encode($awaitingServicesREALTIMEREGISTERSSL).';

            $("#tableServicesList tbody tr").each(function(index) {
                var serviceid = $(this).find("td:first-child").attr("data-element-id");
                
                if(awaitingServicesREALTIMEREGISTERSSL[serviceid])
                {
                    $(this).find("td:nth-child(2)").append("<br><span class=\"label label-warning\">Awaiting Configuration</span>");
                }

            });
        });
    </script>';
    }
    
    $show = false;

    if ($params['filename'] === 'configuressl' && $params['loggedin'] == '1' && isset($_REQUEST['action']) && $_REQUEST['action'] === 'generateCsr')
    {
        $GenerateCsr = new MGModule\RealtimeRegisterSsl\eServices\provisioning\GenerateCSR($params, $_POST);
        echo $GenerateCsr->run();
        die();
    }
    if ($params['templatefile'] === 'clientareacancelrequest')
    {
        try
        {
            $service = \WHMCS\Service\Service::findOrFail($params['id']);
            if ($service->product->servertype === 'RealtimeRegisterSsl')
            {
                $show = true;
            }
        }
        catch (Exception $exc)
        {
            
        }
    }
    elseif ($params['modulename'] === 'RealtimeRegisterSsl')
    {
        $show = true;
    }
    if (!$show)
    {
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

    if (isset($_REQUEST['redirectToProductDetails'], $_REQUEST['serviceID']) && $_REQUEST['redirectToProductDetails'] === 'true' && is_numeric($_REQUEST['serviceID']))
    {
        $ca = new \WHMCS_ClientArea();
        if ($ca->isLoggedIn())
        {
            header('Location: clientarea.php?action=productdetails&id=' . $_REQUEST['serviceID']);
            die();
        }
    }
});

add_hook('InvoicePaid', 1, function($vars)
{
    require_once dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'init.php';
    require_once __DIR__ . DS . 'Loader.php';

    new \MGModule\RealtimeRegisterSsl\Loader();
    \MGModule\RealtimeRegisterSsl\Addon::I(true);
    
    $invoiceGenerator = new \MGModule\RealtimeRegisterSsl\eHelpers\Invoice();
    
    $invoiceInfo = $invoiceGenerator->getInvoiceCreatedInfo($vars['invoiceid']);
    if (!empty($invoiceInfo)) {
        $command = 'SendEmail';
        $postData = array(
            'id'          => $invoiceInfo['service_id'],
            'messagename' => \MGModule\RealtimeRegisterSsl\eServices\EmailTemplateService::RENEWAL_TEMPLATE_ID
        );
        $adminUserName = \MGModule\RealtimeRegisterSsl\eHelpers\Admin::getAdminUserName();
        $results = localAPI($command, $postData, $adminUserName);
        $resultSuccess = $results['result'] == 'success';
        if (!$resultSuccess)
        {
            \MGModule\RealtimeRegisterSsl\eHelpers\Whmcs::savelogActivityRealtimeRegisterSsl('Realtime Register Ssl WHMCS Notifier: Error while sending customer notifications (service ' . $invoiceInfo['service_id'] . '): ' . $results['message'], 0);
        }
    }
    
    $apiConf           = (new \MGModule\RealtimeRegisterSsl\models\apiConfiguration\Repository())->get();
    if(isset($apiConf->renew_new_order) && $apiConf->renew_new_order == '1')
    {
        if (!empty($invoiceInfo)) {
            modulecallfunction("Renew", $invoiceInfo['service_id']);
        }
        return true;
    }
    $invoiceGenerator->invoicePaid($vars['invoiceid']);
});


/*
 *
 * assign ssl summary stats to clieat area page 
 * 
 */

function REALTIMEREGISTERSSL_displaySSLSummaryStats($vars)
{
    
    if (isset($vars['filename'], $vars['templatefile']) && $vars['filename'] == 'clientarea' && $vars['templatefile'] == 'clientareahome')
    {
        try
        {
            require_once __DIR__ . DS . 'Loader.php';
            new \MGModule\RealtimeRegisterSsl\Loader();

            GLOBAl $smarty;

            \MGModule\RealtimeRegisterSsl\Addon::I(true);

            $apiConf           = (new \MGModule\RealtimeRegisterSsl\models\apiConfiguration\Repository())->get();
            $displaySSLSummary = $apiConf->display_ca_summary;
            if (!(bool) $displaySSLSummary)
                return;

            $sslSummaryIntegrationCode = '';

            $titleLang       = \MGModule\RealtimeRegisterSsl\mgLibs\Lang::T('addonCA', 'sslSummary', 'title');
            $totalLang       = \MGModule\RealtimeRegisterSsl\mgLibs\Lang::T('addonCA', 'sslSummary', 'total');
            $unpaidLang      = \MGModule\RealtimeRegisterSsl\mgLibs\Lang::T('addonCA', 'sslSummary', 'unpaid');
            $processingLang  = \MGModule\RealtimeRegisterSsl\mgLibs\Lang::T('addonCA', 'sslSummary', 'processing');
            $expiresSoonLang = \MGModule\RealtimeRegisterSsl\mgLibs\Lang::T('addonCA', 'sslSummary', 'expiresSoon');
            $viewAll         = \MGModule\RealtimeRegisterSsl\mgLibs\Lang::T('viewAll');

            //get ssl statistics
            $sslSummaryStats = new MGModule\RealtimeRegisterSsl\eHelpers\SSLSummary($_SESSION['uid']);

            $totalOrders = $sslSummaryStats->getTotalSSLOrdersCount();

            if ((int) $totalOrders == 0)
                return '';

            $unpaidOrders      = $sslSummaryStats->getUnpaidSSLOrdersCount();
            $processingOrders  = $sslSummaryStats->getProcessingSSLOrdersCount();
            $expiresSoonOrders = $sslSummaryStats->getExpiresSoonSSLOrdersCount();

            $sslSummaryIntegrationCode .= "
                <div class=\"col-sm-12\">
                        <div menuitemname=\"SSL Order Summary\" class=\"panel panel-default panel-accent-gold\">
                                <div class=\"panel-heading\">
                                        <h3 class=\"panel-title\">
                                                <div class=\"pull-right\">
                                                        <a class=\"btn btn-default bg-color-gold btn-xs\"
                                                                href=\"index.php?m=RealtimeRegisterSsl&mg-page=Orders&type=total\">
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
                                                <a href=\"index.php?m=RealtimeRegisterSsl&mg-page=Orders&type=unpaid\">
                                                        <div><i class=\"fa fa-credit-card icon icon col-sm-12\"></i><span>$unpaidLang<u>$unpaidOrders</u></span></div>
                                                </a>
                                        </div>
                                        <div class=\"dsb-box col-sm-4\">
                                                <a href=\"index.php?m=RealtimeRegisterSsl&mg-page=Orders&type=processing\">
                                                        <div><i class=\"fa fa-cogs icon col-sm-12\"></i><span>$processingLang<u>$processingOrders</u></span></div>
                                                </a>
                                        </div>
                                        <div class=\"dsb-box col-sm-4\">
                                                <a href=\"index.php?m=RealtimeRegisterSsl&mg-page=Orders&type=expires_soon\">
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
        catch (\Exception $e)
        {
            
        }
    }
}
add_hook('ClientAreaPage', 1, 'REALTIMEREGISTERSSL_displaySSLSummaryStats');
add_hook('ClientAreaHeadOutput', 999999999999, 'REALTIMEREGISTERSSL_displaySSLSummaryStats');

function REALTIMEREGISTERSSL_loadSSLSummaryCSSStyle($vars)
{
    if (isset($vars['filename'], $vars['templatefile']) && $vars['filename'] == 'clientarea' && $vars['templatefile'] == 'clientareahome')
    {
        return <<<HTML
    <link href="./modules/addons/RealtimeRegisterSsl/templates/clientarea/default/assets/css/sslSummary.css" rel="stylesheet" type="text/css" />
HTML;
    }
}
add_hook('ClientAreaHeadOutput', 1, 'REALTIMEREGISTERSSL_loadSSLSummaryCSSStyle');

function REALTIMEREGISTERSSL_displaySSLSummaryInSidebar($secondarySidebar)
{
    GLOBAL $smarty;
    
    try
    {
        require_once __DIR__ . DS . 'Loader.php';
        new \MGModule\RealtimeRegisterSsl\Loader();

        \MGModule\RealtimeRegisterSsl\Addon::I(true);

        $apiConf           = (new \MGModule\RealtimeRegisterSsl\models\apiConfiguration\Repository())->get();

        if(!isset($apiConf->sidebar_templates) || empty($apiConf->sidebar_templates))
        {
            if (in_array($smarty->tpl_vars['templatefile']->value, array('clientareahome')) || !isset($_SESSION['uid']))
            {
                return;
            }
        }
        else
        {
            if (!in_array($smarty->tpl_vars['templatefile']->value, explode(',', $apiConf->sidebar_templates)) || !isset($_SESSION['uid']))
            {
                return;
            }
        }
        
        $displaySSLSummary = $apiConf->display_ca_summary;
        if (!(bool) $displaySSLSummary)
            return;

        //get ssl statistics
        $sslSummaryStats = new MGModule\RealtimeRegisterSsl\eHelpers\SSLSummary($_SESSION['uid']);

        $totalOrders       = $sslSummaryStats->getTotalSSLOrdersCount();
        if ((int) $totalOrders == 0)
            return '';
        $unpaidOrders      = $sslSummaryStats->getUnpaidSSLOrdersCount();
        $processingOrders  = $sslSummaryStats->getProcessingSSLOrdersCount();
        $expiresSoonOrders = $sslSummaryStats->getExpiresSoonSSLOrdersCount();

        /** @var \WHMCS\View\Menu\Item $secondarySidebar */
        $newMenu = $secondarySidebar->addChild(
                'uniqueMenuSLLSummaryName', array(
            'name'  => 'Home',
            'label' => \MGModule\RealtimeRegisterSsl\mgLibs\Lang::getInstance()->absoluteT('addonCA', 'sslSummary', 'title'),
            'uri'   => '',
            'order' => 99,
            'icon'  => '',
                )
        );
        $newMenu->addChild(
                'uniqueSubMenuSLLSummaryTotal', array(
            'name'  => 'totalOrders',
            'label' => \MGModule\RealtimeRegisterSsl\mgLibs\Lang::getInstance()->absoluteT('addonCA', 'sslSummary', 'total'),
            'uri'   => 'index.php?m=RealtimeRegisterSsl&mg-page=Orders&type=total',
            'order' => 10,
            'badge' => $totalOrders,
                )
        );
        $newMenu->addChild(
                'uniqueSubMenuSLLSummaryUnpaid', array(
            'name'  => 'unpaidOrders',
            'label' => \MGModule\RealtimeRegisterSsl\mgLibs\Lang::getInstance()->absoluteT('addonCA', 'sslSummary', 'unpaid'),
            'uri'   => 'index.php?m=RealtimeRegisterSsl&mg-page=Orders&type=unpaid',
            'order' => 11,
            'badge' => $unpaidOrders,
                )
        );
        $newMenu->addChild(
                'uniqueSubMenuSLLSummaryProcessing', array(
            'name'  => 'processingOrders',
            'label' => \MGModule\RealtimeRegisterSsl\mgLibs\Lang::getInstance()->absoluteT('addonCA', 'sslSummary', 'processing'),
            'uri'   => 'index.php?m=RealtimeRegisterSsl&mg-page=Orders&type=processing',
            'order' => 12,
            'badge' => $processingOrders,
                )
        );
        $newMenu->addChild(
                'uniqueSubMenuSLLSummaryExpires', array(
            'name'  => 'expiresSoonOrders',
            'label' => \MGModule\RealtimeRegisterSsl\mgLibs\Lang::absoluteT('addonCA', 'sslSummary', 'expiresSoon'),
            'uri'   => 'index.php?m=RealtimeRegisterSsl&mg-page=Orders&type=expires_soon',
            'order' => 13,
            'badge' => $expiresSoonOrders,
                )
        );
    }
    catch (\Exception $e)
    {
        
    }
}
add_hook('ClientAreaSecondarySidebar', 1, 'REALTIMEREGISTERSSL_displaySSLSummaryInSidebar');

//unable downgrade certificate sans if active
function REALTIMEREGISTERSSL_unableDowngradeConfigOption($vars)
{
    if (isset($vars['filename'], $vars['templatefile'], $_REQUEST['type']) && $vars['filename'] == 'upgrade' && $_REQUEST['type'] == 'configoptions')
    {
        if (isset($_SESSION['REALTIMEREGISTERSSL_configOpsCustomValidateError']) && $_SESSION['REALTIMEREGISTERSSL_configOpsCustomValidateError'] != '')
        {
            //diplay downgrade error message
            global $smarty;
            $error                                          = $_SESSION['REALTIMEREGISTERSSL_configOpsCustomValidateError'];
            $_SESSION['REALTIMEREGISTERSSL_configOpsCustomValidateError'] = '';
            unset($_SESSION['REALTIMEREGISTERSSL_configOpsCustomValidateError']);

            $smarty->assign("errormessage", $error);
        }

        if (!isset($_REQUEST['step']) || $_REQUEST['step'] != '2')
            return;

        $serviceID = NULL;
        if (isset($_REQUEST['id']) && is_numeric($_REQUEST['id']))
            $serviceID = $_REQUEST['id'];

        if ($serviceID === NULL)
            return;

        $ssl        = new \MGModule\RealtimeRegisterSsl\eRepository\whmcs\service\SSL();
        $sslService = $ssl->getByServiceId($serviceID);
        //check if service id Realtime Register Ssl product
        if (is_null($sslService) && $sslService->module != 'RealtimeRegisterSsl')
            return;

        try
        {
            /** @var \SandwaveIo\RealtimeRegister\Api\ProcessesApi $processApi */
            $processApi = \MGModule\RealtimeRegisterSsl\eProviders\ApiProvider::getInstance()->getApi(\SandwaveIo\RealtimeRegister\Api\ProcessesApi::class);

            $orderStatus = $processApi->get($sslService->remoteid);
        }
        catch (MGModule\RealtimeRegisterSsl\mgLibs\RealtimeRegisterApiException $e)
        {
            return;
        }
        //get config option id related to sans_count and current value
        $CORepo = new \MGModule\RealtimeRegisterSsl\models\whmcs\service\configOptions\Repository($serviceID);
        if (isset($CORepo->{MGModule\RealtimeRegisterSsl\eServices\provisioning\ConfigOptions::OPTION_SANS_COUNT}))
        {
            $sanCountConfigOptionValue = $CORepo->{MGModule\RealtimeRegisterSsl\eServices\provisioning\ConfigOptions::OPTION_SANS_COUNT};
            $sanCountConfigOptionID    = $CORepo->getID(MGModule\RealtimeRegisterSsl\eServices\provisioning\ConfigOptions::OPTION_SANS_COUNT);
        }
        //array(COID => array('minQuantity' => int, 'maxQuantity' => int))
        $configOptionscustomMinMaxQuantities = array(
            $sanCountConfigOptionID => array(
                'min' => $sanCountConfigOptionValue,
                'max' => null
            )
        );
        $whmcs                               = WHMCS\Application::getInstance();
        $configoption                        = $whmcs->get_req_var("configoption");
        $configOptionsService                = new MGModule\RealtimeRegisterSsl\eServices\provisioning\ConfigOptions();
        $configOpsReturn                     = $configOptionsService->validateAndSanitizeQuantityConfigOptions($configoption, $configOptionscustomMinMaxQuantities);

        if ($orderStatus['status'] == 'active' AND $configOpsReturn)
        {
            $_SESSION['REALTIMEREGISTERSSL_configOpsCustomValidateError'] = $configOpsReturn;
            redir('type=configoptions&id=' . $serviceID);
        }
    }

    $step = filter_input(INPUT_POST, 'step', FILTER_VALIDATE_INT);

    if ($vars['type'] == 'configoptions' && $step == 2)
    {
        $promodata = validateUpgradePromo($vars['promocode']);

        //calculate the percentage value
        $serviceData = \WHMCS\Database\Capsule::table('tblhosting')
            ->join('tblproducts', 'tblhosting.packageid', '=', 'tblproducts.id')
            ->select('tblhosting.id', 'tblhosting.packageid', 'tblhosting.domain', 'tblhosting.nextduedate', 'tblhosting.billingcycle', 'tblproducts.servertype')
            ->where('tblhosting.userid', '=', $vars['clientsdetails']['userid'])
            ->where('tblhosting.id', '=', $vars['id'])
            ->first();

        if ($serviceData->servertype == 'RealtimeRegisterSsl')
        {
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

            if( $days < 0 )
            {
                $days = $totaldays;
            }

            $percentage       = $days / $totaldays;
            $upgrades         = $vars['upgrades'];
            $newUpgrades      = [];
            $subtotal         = 0;
            $configoptions    = getCartConfigOptions($serviceData->packageid, $vars['configoptions'], $serviceData->billingcycle);
            $oldconfigoptions = getCartConfigOptions($serviceData->packageid, "", $billingcycle, $serviceData->id);
            $discount = 0;

            foreach ($upgrades as $upgrade)
            {
                foreach ($configoptions as $key2 => $configoption)
                {
                    if ($configoption['optionname'] == $upgrade['configname'])
                    {
                        $testPrice           = $configoptions[$key2]['selectedrecurring'] - $oldconfigoptions[$key2]['selectedrecurring'];
                        $priceWithPercentage = $testPrice * $percentage;
                        $configid            = $configoption['id'];
                    }
                }

                $newPrice = floatval($priceWithPercentage) / floatval($percentage);
                $subtotal += number_format($newPrice, 2);

                if( $GLOBALS["qualifies"] && 0 < $newPrice && (!count($promodata['configoptions']) || in_array($configid, $promodata['configoptions'])) )
                {
                    $itemdiscount = ($promodata["discounttype"] == "Percentage" ? round($newPrice * ($promodata["value"] / 100), 2) : ($newPrice < $promodata["value"] ? $newPrice : $promodata["value"]));
                    $discount += $itemdiscount;
                }

                $upgrade['price'] = formatCurrency($newPrice);
                $newUpgrades[]    = $upgrade;
            }

            $newSubtotal       = formatCurrency($subtotal - $discount);
            $subtotalToCalcTax = $newSubtotal->toNumeric();

            if( $vars['taxenabled'] )
            {
                global $CONFIG;

                if( $vars['taxrate'] )
                {
                    if( $CONFIG["TaxType"] == "Inclusive" )
                    {
                        $inctaxrate         = 1 + $vars['taxrate'] / 100;
                        $tempsubtotal       = $subtotalToCalcTax;
                        $subtotalToCalcTax  = $subtotalToCalcTax / $inctaxrate;
                        $tax                = $tempsubtotal - $subtotalToCalcTax;
                    }
                    else
                    {
                        $tax = $subtotalToCalcTax * $vars['taxrate'] / 100;
                    }
                }

                if( $vars['taxrate2'] )
                {
                    $tempsubtotal = $subtotalToCalcTax;
                    if( $CONFIG["TaxL2Compound"] )
                    {
                        $tempsubtotal += $tax;
                    }

                    if( $CONFIG["TaxType"] == "Inclusive" )
                    {
                        $inctaxrate        = 1 + $vars['taxrate'] / 100;
                        $subtotalToCalcTax = $tempsubtotal / $inctaxrate;
                        $tax2              = $tempsubtotal - $subtotalToCalcTax;
                    }
                    else
                    {
                        $tax2 = $tempsubtotal * $vars['taxrate2'] / 100;
                    }
                }

                $tax         = format_as_currency(round($tax, 2));
                $tax2        = format_as_currency(round($tax2, 2));
                $newSubtotal = formatCurrency($subtotalToCalcTax);
            }

            $newTotal = formatCurrency($newSubtotal->toNumeric() + $tax + $tax2);

            return [
                'upgrades' => $newUpgrades,
                'subtotal' => $newSubtotal,
                'total'    => $newTotal,
                'tax'      => formatCurrency($tax),
                'tax2'     => formatCurrency($tax2),
                'discount' => formatCurrency($discount)
            ];
        }
    }
}
add_hook('ClientAreaPageUpgrade', 1, 'REALTIMEREGISTERSSL_unableDowngradeConfigOption');

function REALTIMEREGISTERSSL_overideProductPricingBasedOnCommission($vars)
{
    
    require_once __DIR__ . DS . 'Loader.php';
    new \MGModule\RealtimeRegisterSsl\Loader();
    MGModule\RealtimeRegisterSsl\Addon::I(true);

    $return       = [];
    //load module products
    $products     = [];
    $productModel = new \MGModule\RealtimeRegisterSsl\models\productConfiguration\Repository();

    if(isset($_SESSION['uid']) && !empty($_SESSION['uid']))
    {
        $clientCurrency = getCurrency($_SESSION['uid']);
    }
    else
    {
        $currency = Capsule::table('tblcurrencies')->where('default', '1')->first();
        $clientCurrency['id'] = isset($_SESSION['currency']) && !empty($_SESSION['currency']) ? $_SESSION['currency'] : $currency->id; 
    }
    //get Realtime Register Ssl all products
    foreach ($productModel->getModuleProducts() as $product)
    {
        
        if($product->servertype != 'RealtimeRegisterSsl')
        {
            continue;
        }
        
        if ($product->id == $vars['pid'])
        {
            $commission = MGModule\RealtimeRegisterSsl\eHelpers\Commission::getCommissionValue($vars);
            
            foreach ($product->pricing as $pricing)
            {
                if ($pricing->currency == $clientCurrency['id'])
                {
                    $priceField           = $vars['proddata']['billingcycle'];
                    if($priceField == 'onetime')
                    {
                        $priceField = 'monthly';
                    }

                    $return = ['recurring' => (float) $pricing->{$priceField} + (float) $pricing->{$priceField} * (float) $commission,];
                }
            }
        }
    }

    return $return;
}

add_hook('OrderProductPricingOverride', 1, 'REALTIMEREGISTERSSL_overideProductPricingBasedOnCommission');

function REALTIMEREGISTERSSL_overideDisaplayedProductPricingBasedOnCommission($vars)
{ 
    global $smarty;
    global $smartyvalues; 
    require_once __DIR__ . DS . 'Loader.php';
    
    new \MGModule\RealtimeRegisterSsl\Loader();
    MGModule\RealtimeRegisterSsl\Addon::I(true);
    if($vars['filename'] == 'cart' || $vars['filename'] == 'index')
    {
    
        switch ($smarty->tpl_vars['templatefile']->value)
        {
            case 'products':
                $products = $smarty->tpl_vars['products']->value;     
                foreach($products as $key => &$product)
                {
                    $productRealtimeRegisterSsl = Capsule::table('tblproducts')
                            ->where('id', $product['pid'])
                            ->where('servertype', 'RealtimeRegisterSsl')
                            ->first();
                    
                    if(isset($productRealtimeRegisterSsl->id) && !empty($productRealtimeRegisterSsl->id))
                    {
                    
                        $pid = $product['pid'];

                        $commission = MGModule\RealtimeRegisterSsl\eHelpers\Commission::getCommissionValue(array('pid' => $pid));
                        $products[$key]['pricing'] = MGModule\RealtimeRegisterSsl\eHelpers\Whmcs::getPricingInfo($pid, $commission);
                    
                    }
                }

                $smartyvalues['products'] = $products;
                $smarty->assign('products', $products);
                break;
            case 'configureproduct':

                $pid = $smarty->tpl_vars['productinfo']->value['pid'];

                $productRealtimeRegisterSsl = Capsule::table('tblproducts')
                            ->where('id', $pid)
                            ->where('servertype', 'RealtimeRegisterSsl')
                            ->first();
                    
                if(isset($productRealtimeRegisterSsl->id) && !empty($productRealtimeRegisterSsl->id))
                {

                    $commission = MGModule\RealtimeRegisterSsl\eHelpers\Commission::getCommissionValue(array('pid' => $pid));
                    $pricing = MGModule\RealtimeRegisterSsl\eHelpers\Whmcs::getPricingInfo($pid, $commission);

                    $smartyvalues['pricing'] = $pricing;
                    $smarty->assign('pricing', $pricing);
                }
                break;
            default:
                break;
        } 
    
    }
    
}
add_hook('ClientAreaHeadOutput', 999999999999, 'REALTIMEREGISTERSSL_overideDisaplayedProductPricingBasedOnCommission');

add_hook('InvoiceCreation', 1, function($vars) {
    
    $invoiceid = $vars['invoiceid'];
    
    $items = Capsule::table('tblinvoiceitems')->where('invoiceid', $invoiceid)->where('type', 'Upgrade')->get();
    
    foreach ($items as $item)
    {
        $description = $item->description;
        
        $upgradeid = $item->relid;
        $upgrade = Capsule::table('tblupgrades')->where('id', $upgradeid)->first();
        
        $serviceid = $upgrade->relid;
        $service = Capsule::table('tblhosting')->where('id', $serviceid)->first();
        
        $productid = $service->packageid;
        $product = Capsule::table('tblproducts')->where('id', $productid)->where('paytype', 'onetime')->where('servertype', 'RealtimeRegisterSsl')->first();
        
        if(isset($product->configoption7) && !empty($product->configoption7))
        {
            
            if (strpos($description, '00/00/0000') !== false) {
                
                $description = str_replace('- 00/00/0000', '', $description);
                $length = strlen($description);
                $description = substr($description, 0, $length-13);
                
                Capsule::table('tblinvoiceitems')->where('id', $item->id)->update(array('description' => trim($description)));
                                
            }
            
        }
        
        
        
    }
    
});


add_hook('ClientAreaHeadOutput', 1, function($vars) {
    $template = $vars['template'];
    return <<<HTML
    <style>
    .hidden {
        display:none;
    }
    </style>
HTML;

});

add_hook('InvoiceCreationPreEmail', 1, function($vars)
{
    //get invoice data
    $command  = 'GetInvoice';
    $postData = ['invoiceid' => $vars['invoiceid']];

    $results = localAPI($command, $postData);

    if ($results['result'] == 'success')
    {
        $invoiceItem          = $results['items']['item'];
        $userID               = $results['userid'];
        $invoiceID            = $results['invoiceid'];
        $newPrices            = [];
        $itemdescriptionArray = [];
        $itemamountArray      = [];
        $itemtaxedArray       = [];

        foreach ($invoiceItem as $item)
        {
            if ($item['type'] == 'Upgrade')
            {
                //check if this is config options update and get the service id
                $itemID    = $item['id'];
                $upgradeID = $item['relid'];

                $upgradeData = \WHMCS\Database\Capsule::table('tblupgrades')
                    ->where('id', '=', $upgradeID)
                    ->first();

                if ($upgradeData->type == 'configoptions')
                {
                    $serviceID   = $upgradeData->relid;
                    $serviceData = \WHMCS\Database\Capsule::table('tblhosting')
                        ->join('tblproducts', 'tblhosting.packageid', '=', 'tblproducts.id')
                        ->select('tblhosting.id', 'tblhosting.orderid', 'tblhosting.packageid', 'tblhosting.domain', 'tblhosting.nextduedate', 'tblhosting.billingcycle', 'tblproducts.servertype')
                        ->where('tblhosting.userid', '=', $userID)
                        ->where('tblhosting.id', '=', $serviceID)
                        ->first();

                    if ($serviceData->servertype == 'RealtimeRegisterSsl')
                    {
                        $isRealtimeRegisterSslProduct = true;
                        $upgradesData      = \WHMCS\Database\Capsule::table('tblupgrades')
                            ->select('recurringchange', 'originalvalue')
                            ->where('id', '=', $item['relid'])
                            ->first();

                        $configOptionID = explode('=>', $upgradesData->originalvalue)[0];

                        $newPrice                      = formatCurrency(floatval($upgradesData->recurringchange));
                        $newPrices[$configOptionID]    = $newPrice->toNumeric();
                        $itemdescriptionArray[$itemID] = $item['description'];
                        $itemamountArray[$itemID]      = $newPrice->toNumeric();
                        $itemtaxedArray[$itemID]       = $item['taxed'];
                    }
                }
            }
            elseif($item['type'] == '' && !$item['relid'] && $isRealtimeRegisterSslProduct)
            {
                $promoItemID = $item['id'];
                $description = $item['description'];
                $promoTaxed  = $item['taxed'];
                $tmp1        = explode(':', $description);
                $tmp2        = explode('-', $tmp1[1]);
                $promocode   = trim($tmp2[0]);

                if ($promocode && $promocode != '' && $promocode != 'The promotion code entered does not exist' && $promoItemID != '' && !empty($newPrices))
                {
                    $promodata    = validateUpgradePromo($promocode);
                    $itemdiscount = 0;

                    foreach ($newPrices as $configid => $price )
                    {
                        if (in_array($configid, $promodata['configoptions']))
                        {
                            $itemdiscount += ($promodata["discounttype"] == "Percentage" ? round($price * ($promodata["value"] / 100), 2) : ($price < $promodata["value"] ? $newPrice : $promodata["value"]));
                        }
                    }

                    $itemdescriptionArray[$promoItemID] = $description;
                    $itemamountArray[$promoItemID]      = -1 * abs($itemdiscount);
                    $itemtaxedArray[$promoItemID]       = $promoTaxed;
                    $newPrices                          = [];
                }
            }
        }

        if (!empty($itemdescriptionArray) && !empty($itemamountArray) && !empty($itemtaxedArray))
        {
            $command2  = 'UpdateInvoice';
            $postData2 = [
                'invoiceid'       => $invoiceID,
                'itemdescription' => $itemdescriptionArray,
                'itemamount'      => $itemamountArray,
                'itemtaxed'       => $itemtaxedArray
            ];

            $results = localAPI($command2, $postData2);

            $lastOrder = \WHMCS\Database\Capsule::table('tblorders')->where('userid', $userID)->orderBy('id', 'DESC')->first();

            \WHMCS\Database\Capsule::table('tblorders')->where('id', $lastOrder->id)->update([
                'amount' => reset($itemamountArray)
            ]);

            if (!$results['result'] == 'success')
            {
                logModuleCall(
                    'RealtimeRegisterSsl',
                    $command2,
                    $postData2,
                    $results
                );
            }
        }
    }
    else
    {
        logModuleCall(
            'RealtimeRegisterSsl',
            $command,
            $postData,
            $results
        );
    }
});

add_hook('AdminAreaFooterOutput', 1, function($vars)
{
    if ($vars['filename'] == 'clientsservices' && $_GET['userid'] && $_GET['id']) {
        $hosting = \WHMCS\Database\Capsule::table('tblhosting')
            ->join('tblproducts', 'tblproducts.id', '=', 'tblhosting.packageid')
            ->where('tblhosting.id', $_GET['id'])
            ->first();
        if($hosting->servertype != 'RealtimeRegisterSsl') {
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

    if($vars['template'] == 'twenty-one' && $vars['module'] == 'RealtimeRegisterSsl') {

        return <<<HTML
<script type="text/javascript">
$(document).ready(function (){
    $('.modal-header.panel-heading').css({'display':'block'});
});
</script>
HTML;

    }
});