<?php

namespace AddonModule\RealtimeRegisterSsl;

use AddonModule\RealtimeRegisterSsl\addonLibs\process\AbstractConfiguration;
use AddonModule\RealtimeRegisterSsl\eHelpers\Invoice as InvoiceHelper;
use AddonModule\RealtimeRegisterSsl\eModels\whmcs\service\SSL;
use AddonModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\KeyToIdMapping;
use AddonModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\Products;
use AddonModule\RealtimeRegisterSsl\eServices\ConfigurableOptionService;
use AddonModule\RealtimeRegisterSsl\eServices\EmailTemplateService;
use AddonModule\RealtimeRegisterSsl\eServices\provisioning\ConfigOptions;
use AddonModule\RealtimeRegisterSsl\models\apiConfiguration\Repository as APIConfigurationRepo;
use AddonModule\RealtimeRegisterSsl\models\logs\Repository as LogsRepo;
use AddonModule\RealtimeRegisterSsl\models\orders\Repository as OrdersRepo;
use AddonModule\RealtimeRegisterSsl\models\productConfiguration\Repository as WhmcsProducts;
use AddonModule\RealtimeRegisterSsl\models\productPrice\Repository as ProductPriceRepo;
use AddonModule\RealtimeRegisterSsl\models\userDiscount\Repository as UserDiscountRepo;
use WHMCS\Database\Capsule;

class Configuration extends AbstractConfiguration
{
    /**
     * Enable or disable debug mode in your module.
     * @var bool
     */
    public $debug = false;

    /**
     * Module name in WHMCS configuration
     * @var string
     */
    public $systemName = 'realtimeregister_ssl';

    /**
     * Module name visible on addon module page
     * @var string
     */
    public $name = 'Realtime Register SSL WHMCS';

    /**
     * Module description
     * @var string
     */
    public $description = '';

    /**
     * Module name in client area
     * @var string
     */
    public $clientareaName = 'Realtime Register SSL WHMCS';

    /**
     * Encryption hash. Used in ORM
     * @var string
     */
    public $encryptHash = 'uUc1Y8cWxDOAzlq11lBwelqzo6PGMTA0dbHaKQ109psefoJgIFMOgmReKCZbpCYpDSnrtfjmCIUyplaBJaUh40auDALprOHtj1g92ZRBS6S94IbZWaeZRYkG1f81h6qLMYEOr016RurCnmodFCWdMkTqrlVBvH249gzXPduKQVXpN9hooComaRPY5jZD6s8GdfR5E_BNP3v8Ui8RrdqMPST_8quMW48LhHY88xCvSWwDNjkC2tCAaK67Id2NjzIdoNTHUMISRg81nHX8ZGcbP74mxixo_ASd8YoWnDCAs8yiT4t0PwKRO_y3C1kDo69Nxz1YYt4tY1VzOD_DFBulAA5NCJLfogroo';

    /**
     * Module version
     * @var string
     */
    public $version = '1.0.0';
    public $tablePrefix = '';
    public $modelRegister = [];

    private static function updateProductPricing()
    {
        $products = Capsule::table('tblproducts')
            ->select(['id', 'paytype'])
            ->where('configoption1', 'LIKE', 'ssl_%')
            ->get();
        foreach ($products as $product) {
            self::updatePricing($product->id, $product->paytype);
        }
    }

    private static function updatePricing($productId, $paytype)
    {
        $optionGroupResult = Capsule::table('tblproductconfiggroups')
            ->select('id')
            ->where('description', '=', 'Auto generated by module - RealtimeRegisterSSL #' . $productId)
            ->first();

        if ($optionGroupResult == null) {
            return;
        }

        $configOptions = Capsule::table('tblproductconfigoptions')
            ->select()
            ->where('gid', '=', $optionGroupResult->id)
            ->orderBy('gid', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $currentSansOptionSubId = 0;
        $currentWildcardSansOptionSubId = 0;

        foreach ($configOptions as $configOption) {
            $configOptionSubs = Capsule::table('tblproductconfigoptionssub')
                ->select()
                ->where('configid', '=', $configOption->id)
                ->orderBy('sortorder')
                ->get();
            if (str_contains($configOption->optionname, 'years|')) {
                foreach ($configOptionSubs as $i => $configOptionSub) {
                    $currentPrices = Capsule::table('tblpricing')
                        ->where('relid', '=', $configOptionSub->id)
                        ->where('type', '=', 'configoptions')
                        ->get();

                    foreach ($currentPrices as $price) {
                        switch ($i) {
                            case 0:
                            {
                                $productPrice = floatval($price->annually) > 0.00 ? $price->annually : $price->monthly;
                                Capsule::table('tblpricing')
                                    ->where('relid', '=', $productId)
                                    ->where('currency', '=', $price->currency)
                                    ->where('type', '=', 'product')
                                    ->update(['annually' => $productPrice, "monthly" => $paytype === 'onetime' ? $productPrice : '-1.00']);
                                Capsule::table('tblhostingconfigoptions')
                                    ->where('configid', '=', $configOption->id)
                                    ->where('optionid', '=', $configOptionSub->id)
                                    ->delete();
                                break;
                            }
                            case 1:
                            {
                                $productPrice = floatval($price->biennially) > 0.00 ? $price->biennially : $price->monthly;
                                Capsule::table('tblpricing')
                                    ->where('relid', '=', $productId)
                                    ->where('currency', '=', $price->currency)
                                    ->where('type', '=', 'product')
                                    ->update(['biennially' => $productPrice]);
                                Capsule::table('tblhostingconfigoptions')
                                    ->where('configid', '=', $configOption->id)
                                    ->where('optionid', '=', $configOptionSub->id)
                                    ->delete();
                                break;
                            }
                            case 2:
                            {
                                $productPrice = floatval($price->triennially) > 0.00 ? $price->triennially : $price->monthly;
                                Capsule::table('tblpricing')
                                    ->where('relid', '=', $productId)
                                    ->where('currency', '=', $price->currency)
                                    ->where('type', '=', 'product')
                                    ->update(['triennially' => $productPrice]);
                                Capsule::table('tblhostingconfigoptions')
                                    ->where('configid', '=', $configOption->id)
                                    ->where('optionid', '=', $configOptionSub->id)
                                    ->delete();
                                break;
                            }
                            default:
                                Capsule::table('tblhostingconfigoptions')
                                    ->where('configid', '=', $configOption->id)
                                    ->where('optionid', '=', $configOptionSub->id)
                                    ->delete();
                                break;
                        }
                    }
                    Capsule::table('tblproductconfigoptionssub')
                        ->select()
                        ->where('id', '=', $configOptionSub->id)
                        ->delete();
                }

                Capsule::table('tblproductconfigoptions')
                    ->select()
                    ->where('id', '=', $configOption->id)
                    ->delete();


            } elseif (str_contains($configOption->optionname, ConfigOptions::OPTION_SANS_COUNT)) {
                $configOptionSub = $configOptionSubs[0];
                preg_match_all('/\d+/', $configOption->optionname, $matches);

                $period = intval($matches[0][0]);
                $currentPrices = Capsule::table('tblpricing')
                    ->where('relid', '=', $configOptionSub->id)
                    ->where('type', '=', 'configoptions')
                    ->get();

                foreach ($currentPrices as $price) {
                    switch ($period) {
                        case 1:
                        {
                            $currentSansOptionSubId = $configOptionSub->id;
                            $productPrice = floatval($price->annually) > 0.00 ? $price->annually : $price->monthly;
                            Capsule::table('tblpricing')
                                ->where('relid', '=', $configOptionSub->id)
                                ->where('currency', '=', $price->currency)
                                ->where('type', '=', 'configoptions')
                                ->update(['annually' => $productPrice, "monthly" => $paytype === 'onetime' ? $productPrice : '-1.00']);
                            $newOptionName = preg_replace("/\( years\)/", "",
                                preg_replace("/1/", "", $configOption->optionname)
                            );
                            Capsule::table('tblproductconfigoptions')
                                ->select()
                                ->where('id', '=', $configOption->id)
                                ->update(["optionname" => $newOptionName]);
                            break;
                        }
                        case 2:
                            $productPrice = floatval($price->biennially) > 0.00 ? $price->biennially : $price->monthly;
                            Capsule::table('tblpricing')
                                ->where('relid', '=', $currentSansOptionSubId)
                                ->where('currency', '=', $price->currency)
                                ->where('type', '=', 'configoptions')
                                ->update(['biennially' => $productPrice]);
                            Capsule::table('tblproductconfigoptions')
                                ->select()
                                ->where('id', '=', $configOption->id)
                                ->delete();
                            break;
                        case 3:
                            $productPrice = floatval($price->triennially) > 0.00 ? $price->triennially : $price->monthly;
                            Capsule::table('tblpricing')
                                ->where('relid', '=', $currentSansOptionSubId)
                                ->where('currency', '=', $price->currency)
                                ->where('type', '=', 'configoptions')
                                ->update(['triennially' => $productPrice]);
                            Capsule::table('tblproductconfigoptions')
                                ->select()
                                ->where('id', '=', $configOption->id)
                                ->delete();
                            break;
                        case 4:
                        case 5:
                            Capsule::table('tblproductconfigoptions')
                                ->select()
                                ->where('id', '=', $configOption->id)
                                ->delete();
                            break;
                        default:
                            break;
                    }
                }
            } elseif (str_contains($configOption->optionname, ConfigOptions::OPTION_SANS_WILDCARD_COUNT)) {
                $configOptionSub = $configOptionSubs[0];
                preg_match_all('/\d+/', $configOption->optionname, $matches);

                $period = intval($matches[0][0]);
                $currentPrices = Capsule::table('tblpricing')
                    ->where('relid', '=', $configOptionSub->id)
                    ->where('type', '=', 'configoptions')
                    ->get();

                foreach ($currentPrices as $price) {
                    switch ($period) {
                        case 1:
                        {
                            $currentWildcardSansOptionSubId = $configOptionSub->id;
                            $productPrice = floatval($price->annually) > 0.00 ? $price->annually : $price->monthly;
                            Capsule::table('tblpricing')
                                ->where('relid', '=', $configOptionSub->id)
                                ->where('currency', '=', $price->currency)
                                ->where('type', '=', 'configoptions')
                                ->update(['annually' => $productPrice, "monthly" => $paytype === 'onetime' ? $productPrice : '-1.00']);
                            $newOptionName = preg_replace("/\( years\)/", "",
                                preg_replace("/1/", "", $configOption->optionname)
                            );
                            Capsule::table('tblproductconfigoptions')
                                ->select()
                                ->where('id', '=', $configOption->id)
                                ->update(["optionname" => $newOptionName]);
                            break;
                        }
                        case 2:
                            $productPrice = floatval($price->biennially) > 0.00 ? $price->annually : $price->monthly;
                            Capsule::table('tblpricing')
                                ->where('relid', '=', $currentWildcardSansOptionSubId)
                                ->where('currency', '=', $price->currency)
                                ->where('type', '=', 'configoptions')
                                ->update(['biennially' => $productPrice]);
                            Capsule::table('tblproductconfigoptions')
                                ->select()
                                ->where('id', '=', $configOption->id)
                                ->delete();
                            break;
                        case 3:
                            $productPrice = floatval($price->triennially) > 0.00 ? $price->annually : $price->monthly;
                            Capsule::table('tblpricing')
                                ->where('relid', '=', $currentWildcardSansOptionSubId)
                                ->where('currency', '=', $price->currency)
                                ->where('type', '=', 'configoptions')
                                ->update(['triennially' => $productPrice]);
                            Capsule::table('tblproductconfigoptions')
                                ->select()
                                ->where('id', '=', $configOption->id)
                                ->delete();
                            break;
                        case 4:
                        case 5:
                            Capsule::table('tblproductconfigoptions')
                                ->select()
                                ->where('id', '=', $configOption->id)
                                ->delete();
                            break;
                        default:
                            break;
                    }
                }
            }
        }
    }

    private static function insertHiddenFields()
    {
        $products = Capsule::table('tblproducts')
            ->select(['id'])
            ->where('servertype', '=', 'realtimeregister_ssl')
            ->get();
        foreach ($products as $product) {
            if (Capsule::table('tblcustomfields')
                    ->where('relid', '=', $product->id)
                    ->where('fieldtype', '=', 'hidden')
                    ->first() == null) {
                ConfigurableOptionService::createHiddenField($product->id);
            }
        }
    }

    /**
     * Addon module visible in module
     */
    function getAddonMenu(): array
    {
        return [
            'apiConfiguration' => ['icon' => 'fa fa-key'],
            'productsCreator' => ['icon' => 'fa fa-magic'],
            'productsConfiguration' => ['icon' => 'fa fa-edit'],
            'orders' => ['icon' => 'fa fa-shopping-cart'],
            'logs' => ['icon' => 'fa fa-list'],
            'crons' => ['icon' => 'fa fa-refresh'],
        ];
    }

    /**
     * Addon module visible in client area
     */
    function getClientMenu(): array
    {
        return [
            'Orders' => ['icon' => 'glyphicon glyphicon-home']
        ];
    }

    /**
     * Provisioning menu visible in admin area
     */
    function getServerMenu(): array
    {
        return [
            'configuration' => ['icon' => 'glyphicon glyphicon-cog']
        ];
    }

    /**
     * Return names of WHMCS product config fields
     * required if you want to use default WHMCS product configuration
     * max 20 fields
     *
     * if you want to use own product configuration use example
     * /models/customWHMCS/product to define own configuration model
     *
     * @return array
     */
    public function getServerWHMCSConfig()
    {
        return ['text_name', 'text_name2', 'checkbox_name', 'onoff', 'pass', 'some_option', 'some_option2', 'radio_field'];
    }

    /**
     * Addon module configuration visible in admin area. This is standard WHMCS configuration
     * @return array
     */
    public function getAddonWHMCSConfig()
    {
        return [];
    }

    /**
     * Run When Module Install
     */
    function activate()
    {
        (new APIConfigurationRepo())->createApiConfigurationTable();
        (new ProductPriceRepo())->createApiProductsPricesTable();
        (new UserDiscountRepo())->createUserDiscountTable();
        (new LogsRepo())->createLogsTable();
        (new OrdersRepo())->createOrdersTable();
        (new KeyToIdMapping())->createTable();
        InvoiceHelper::createInfosTable();
        InvoiceHelper::createPendingPaymentInvoice();
        EmailTemplateService::createConfigurationTemplate();
        EmailTemplateService::createCertificateTemplate();
        EmailTemplateService::createExpireNotificationTemplate();
        EmailTemplateService::createRenewalTemplate();
        EmailTemplateService::createReissueTemplate();
        EmailTemplateService::createValidationInformationTemplate();
        self::installTasks();
    }

    /**
     * Do something after module deactivate. You can status and description
     */
    function deactivate()
    {
        (new APIConfigurationRepo())->dropApiConfigurationTable();
        (new ProductPriceRepo())->dropApiProductsPricesTable();
        (new UserDiscountRepo())->dropUserDiscountTable();
        (new LogsRepo())->dropLogsTable();
        (new OrdersRepo())->dropOrdersTable();
        (new KeyToIdMapping())->dropTable();
        (new WhmcsProducts())->dropProducts();
        Products::getInstance()::dropTable();
        EmailTemplateService::deleteConfigurationTemplate();
        EmailTemplateService::deleteCertificateTemplate();
        EmailTemplateService::deleteExpireNotificationTemplate();
        EmailTemplateService::deleteRenewalTemplate();
        EmailTemplateService::deleteReissueTemplate();
        EmailTemplateService::deleteValidationInformationTemplate();
    }

    /**
     * Do something after module upgrade
     */
    function upgrade(array $vars = [])
    {
        EmailTemplateService::updateConfigurationTemplate();
        EmailTemplateService::updateRenewalTemplate();
        EmailTemplateService::updateReissueTemplate();
        EmailTemplateService::updateValidationInformationTemplate();
        (new APIConfigurationRepo())->updateApiConfigurationTable();
        (new LogsRepo())->updateLogsTable();
        (new OrdersRepo())->updateOrdersTable();
        self::renameSSLOrderStatuses();
        self::updateProductPricing();
        self::insertHiddenFields();
        self::installTasks();
    }

    static function renameSSLOrderStatuses()
    {
        foreach (SSL::all() as $sslOrder) {
            if ($sslOrder->status === 'active') {
                $sslOrder->status = SSL::ACTIVE;
            }
            if ($sslOrder->getSSLStatus() === 'active' || $sslOrder->getSSLStatus() === 'Active') {
                $sslOrder->setSSLStatus('ACTIVE');
            }
            $sslOrder->save();
        }
    }

    static function installTasks()
    {
        /**
         * We now run our crontasks via the cron setup of WHMCS tasks,
         * these can all be disabled via the admin
         */
        global $CONFIG;

        require_once __DIR__ . DS . 'Loader.php';
        new Loader();

        \AddonModule\RealtimeRegisterSsl\cron\AutomaticSynchronisation::register();
        \AddonModule\RealtimeRegisterSsl\cron\ProcessingOrders::register();
        \AddonModule\RealtimeRegisterSsl\cron\DailyStatusUpdater::register();
        \AddonModule\RealtimeRegisterSsl\cron\CertificateStatisticsLoader::register();
        \AddonModule\RealtimeRegisterSsl\cron\ExpiryHandler::register();
        \AddonModule\RealtimeRegisterSsl\cron\CertificateSender::register();
        \AddonModule\RealtimeRegisterSsl\cron\PriceUpdater::register();
        \AddonModule\RealtimeRegisterSsl\cron\CertificateDetailsUpdater::register();
        \AddonModule\RealtimeRegisterSsl\cron\InstallCertificates::register();
    }


    public function getAuthor()
    {
        return 'Realtime Register';
    }
}
