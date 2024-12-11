<?php

namespace AddonModule\RealtimeRegisterSsl;

use AddonModule\RealtimeRegisterSsl\addonLibs\process\AbstractConfiguration;
use AddonModule\RealtimeRegisterSsl\eHelpers\Invoice as InvoiceHelper;
use AddonModule\RealtimeRegisterSsl\eModels\whmcs\service\SSL;
use AddonModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\KeyToIdMapping;
use AddonModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\Products;
use AddonModule\RealtimeRegisterSsl\eServices\EmailTemplateService;
use AddonModule\RealtimeRegisterSsl\models\apiConfiguration\Repository as APIConfigurationRepo;
use AddonModule\RealtimeRegisterSsl\models\logs\Repository as LogsRepo;
use AddonModule\RealtimeRegisterSsl\models\orders\Repository as OrdersRepo;
use AddonModule\RealtimeRegisterSsl\models\productConfiguration\Repository as WhmcsProducts;
use AddonModule\RealtimeRegisterSsl\models\productPrice\Repository as ProductPriceRepo;
use AddonModule\RealtimeRegisterSsl\models\userDiscount\Repository as UserDiscountRepo;


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
    public $version = '0.4.6';

    private static string $LEGACY_TABLE_PREFIX = 'mgfw_';


    /**
     * Table prefix. This prefix is used in database models. You have to change it! 
     * @var type 
     */
    public $tablePrefix   = '';
    public $modelRegister = [];

    /**
     * Addon module visible in module
     */
    function getAddonMenu(): array
    {
        return [
            'apiConfiguration' => ['icon' => 'fa fa-key'],
            'productsCreator' => ['icon' => 'fa fa-magic'],
            'productsConfiguration' => ['icon' => 'fa fa-edit'],
            'userDiscounts' => ['icon' => 'fa fa-user-plus'],
            'orders' => ['icon' => 'fa fa-shopping-cart'],
            'logs' => ['icon' => 'fa fa-list']
        ];
    }

    /**
     * Addon module visible in client area
     * @return array
     */
    function getClienMenu()
    {
        return [
            'Orders' => ['icon' => 'glyphicon glyphicon-home']
        ];
    }

    /**
     * Provisioning menu visible in admin area
     * @return array
     */
    function getServerMenu()
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
        return ['text_name','text_name2','checkbox_name','onoff','pass','some_option','some_option2','radio_field'];
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
     *
     * @return array
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
    }

    /**
     * Do something after module deactivate. You can status and description
     * @return array
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
    }

    /**
     * Do something after module upgrade
     */
    function upgrade(array $vars = [])
    {
        EmailTemplateService::updateConfigurationTemplate();
        EmailTemplateService::updateRenewalTemplate();
        EmailTemplateService::updateReissueTemplate();
        InvoiceHelper::updateInfosTable(self::$LEGACY_TABLE_PREFIX);
        InvoiceHelper::updateInfosTable(self::$LEGACY_TABLE_PREFIX);
        Products::updateTable(self::$LEGACY_TABLE_PREFIX);
        (new APIConfigurationRepo())->updateApiConfigurationTable(self::$LEGACY_TABLE_PREFIX);
        (new ProductPriceRepo())->updateApiProductsPricesTable(self::$LEGACY_TABLE_PREFIX);
        (new UserDiscountRepo())->updateUserDiscountTable(self::$LEGACY_TABLE_PREFIX);
        (new KeyToIdMapping())->updateTable(self::$LEGACY_TABLE_PREFIX);
        (new LogsRepo())->updateLogsTable();
        (new OrdersRepo())->updateOrdersTable();
        self::renameSSLOrderStatuses();
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

    public function getAuthor()
    {
        return 'Realtime Register';
    }
}
