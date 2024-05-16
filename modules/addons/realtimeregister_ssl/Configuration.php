<?php

namespace MGModule\RealtimeRegisterSsl;

use MGModule\RealtimeRegisterSsl\mgLibs\process\AbstractConfiguration;
use MGModule\RealtimeRegisterSsl\models\apiConfiguration\Repository as APIConfigurationRepo;
use MGModule\RealtimeRegisterSsl\models\productPrice\Repository as ProductPriceRepo;
use MGModule\RealtimeRegisterSsl\models\userCommission\Repository as UserCommissionRepo;
use MGModule\RealtimeRegisterSsl\models\logs\Repository as LogsRepo;
use MGModule\RealtimeRegisterSsl\models\orders\Repository as OrdersRepo;
use MGModule\RealtimeRegisterSsl\eServices\EmailTemplateService;
use MGModule\RealtimeRegisterSsl\eHelpers\Invoice as InvoiceHelper;
use MGModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\KeyToIdMapping;

/**
 * Module Configuration
 *
 * @author Michal Czech <michael@modulesgarden.com>
 * @SuppressWarnings("unused")
 */
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
    public $name = 'Realtime Register Ssl WHMCS';

    /**
     * Module description
     * @var string
     */
    public $description = '';

    /**
     * Module name in client area
     * @var string
     */
    public $clientareaName = 'Realtime Register Ssl WHMCS';

    /**
     * Encryption hash. Used in ORM 
     * @var string
     */
    public $encryptHash = 'uUc1Y8cWxDOAzlq11lBwelqzo6PGMTA0dbHaKQ109psefoJgIFMOgmReKCZbpCYpDSnrtfjmCIUyplaBJaUh40auDALprOHtj1g92ZRBS6S94IbZWaeZRYkG1f81h6qLMYEOr016RurCnmodFCWdMkTqrlVBvH249gzXPduKQVXpN9hooComaRPY5jZD6s8GdfR5E_BNP3v8Ui8RrdqMPST_8quMW48LhHY88xCvSWwDNjkC2tCAaK67Id2NjzIdoNTHUMISRg81nHX8ZGcbP74mxixo_ASd8YoWnDCAs8yiT4t0PwKRO_y3C1kDo69Nxz1YYt4tY1VzOD_DFBulAA5NCJLfogroo';

    /**
     * Module version
     * @var string
     */
    public $version = '1.0';

    /**
     * Module author
     * @var string
     */
    public $author = 'Realtime Register';

    /**
     * Table prefix. This prefix is used in database models. You have to change it! 
     * @var type 
     */
    public $tablePrefix   = 'mgfw_';
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
            'userCommissions' => ['icon' => 'fa fa-user-plus'],
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
     * @author Michal Czech <michael@modulesgarden.com>
     * @return array
     */
    function activate()
    {
        (new APIConfigurationRepo())->createApiConfigurationTable();
        (new ProductPriceRepo())->createApiProductsPricesTable();
        (new UserCommissionRepo())->createUserCommissionTable();
        (new LogsRepo())->createLogsTable();
        (new OrdersRepo())->createOrdersTable();
        (new KeyToIdMapping())->createTable();
        EmailTemplateService::createConfigurationTemplate();
        EmailTemplateService::createCertyficateTemplate();
        EmailTemplateService::createExpireNotificationTemplate();
        EmailTemplateService::createRenewalTemplate();
        EmailTemplateService::createReissueTemplate();
        InvoiceHelper::createInfosTable();
        InvoiceHelper::createPendingPaymentInvoice();
    }

    /**
     * Do something after module deactivate. You can status and description
     * @return array
     */
    function deactivate()
    {
        (new APIConfigurationRepo())->dropApiConfigurationTable();
        (new ProductPriceRepo())->dropApiProductsPricesTable();
        (new UserCommissionRepo())->dropUserCommissionTable();
        (new LogsRepo())->dropLogsTable();
        (new OrdersRepo())->dropOrdersTable();
        (new KeyToIdMapping())->dropTable();
        EmailTemplateService::deleteConfigurationTemplate();
        EmailTemplateService::deleteCertyficateTemplate();
        EmailTemplateService::deleteExpireNotificationTemplate();
        EmailTemplateService::deleteRenewalTemplate();
        EmailTemplateService::deleteReissueTemplate();
    }

    /**
     * Do something after module upgrade
     */
    function upgrade(array $vars)
    {
        EmailTemplateService::createExpireNotificationTemplate();
        EmailTemplateService::updateConfigurationTemplate();
        EmailTemplateService::updateRenewalTemplate();
        EmailTemplateService::updateReissueTemplate();
        InvoiceHelper::createInfosTable();
        InvoiceHelper::createPendingPaymentInvoice();
        (new APIConfigurationRepo())->updateApiConfigurationTable();
        (new ProductPriceRepo())->updateApiProductsPricesTable();
        (new UserCommissionRepo())->updateUserCommissionTable();
        (new LogsRepo())->updateLogsTable();
        (new OrdersRepo())->updateOrdersTable();
    }
}
