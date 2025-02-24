<?php

namespace AddonModule\RealtimeRegisterSsl\controllers\addon\admin;

use AddonModule\RealtimeRegisterSsl\addonLibs\forms\Popup;
use AddonModule\RealtimeRegisterSsl\addonLibs\forms\TextField;
use AddonModule\RealtimeRegisterSsl\addonLibs\Lang;
use AddonModule\RealtimeRegisterSsl\addonLibs\process\AbstractController;
use AddonModule\RealtimeRegisterSsl\addonLibs\Smarty;
use AddonModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\KeyToIdMapping;
use AddonModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\Products;
use AddonModule\RealtimeRegisterSsl\eServices\ConfigurableOptionService;
use AddonModule\RealtimeRegisterSsl\eServices\provisioning\ConfigOptions as C;
use AddonModule\RealtimeRegisterSsl\models\apiConfiguration\Repository;
use AddonModule\RealtimeRegisterSsl\models\productPrice\Repository as ApiProductPriceRepo;
use AddonModule\RealtimeRegisterSsl\models\whmcs\pricing\BillingCycle;
use Exception;
use Illuminate\Database\Capsule\Manager as Capsule;
use WHMCS\Product\Group;

class ProductsCreator extends AbstractController
{
    private Products $apiProductsRepo;

    /**
     * This is default page.
     */
    public function indexHTML($input = [], $vars = []): array
    {
        try {
            $this->apiProductsRepo = Products::getInstance();
            $productModel = new \AddonModule\RealtimeRegisterSsl\models\productConfiguration\Repository();
            $vars['currencies'] = $productModel->getAllCurrencies();
            $vars['apiProducts'] = $this->apiProductsRepo->getAllProducts();
            $vars['apiProductsCount'] = count($this->apiProductsRepo->getAllProducts());
            $vars['productGroups'] = Group::all();

            if (count($vars['productGroups']) === 0) {
                throw new Exception('no_product_group_found');
            }

            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($input['createMass'])) {
                $this->saveProducts($vars['currencies'], $input);
                $vars['success'] = Lang::T('messages', 'mass_product_created');
            }

            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($input['createSingle'])) {
                $this->saveProducts($vars['currencies'], $input);
                $vars['success'] = Lang::T('messages', 'single_product_created');
            }
        } catch (Exception $e) {
            $vars['formError'] = Lang::T('messages', $e->getMessage());
        }

        return [
            'tpl' => 'products_creator',
            'vars' => $vars
        ];
    }

    /**
     * @throws Exception
     */
    public function saveProduct($input = []): int
    {
        if (isset($input[C::API_PRODUCT_ID]) && $input[C::API_PRODUCT_ID] == 0) {
            throw new Exception('api_product_not_chosen');
        }

        $productData = [
            'type' => 'other',
            'gid' => $input['gid'],
            'name' => $input['name'],
            'paytype' => $input['paytype'] ?: 'recurring',
            'servertype' => 'realtimeregister_ssl',
            'hidden' => $input['hidden'] ?: 1,
            'autosetup' => $input['autosetup'],
            C::PRICE_AUTO_DOWNLOAD => $input[C::PRICE_AUTO_DOWNLOAD] ?: '',
            C::API_PRODUCT_ID => $input[C::API_PRODUCT_ID],
            C::API_PRODUCT_MONTHS => $input[C::API_PRODUCT_MONTHS],
            C::PRODUCT_ENABLE_SAN => $input[C::PRODUCT_ENABLE_SAN] ?: '',
            C::PRODUCT_INCLUDED_SANS => $input[C::PRODUCT_INCLUDED_SANS] ?: 0,
            C::PRODUCT_INCLUDED_SANS_WILDCARD => $input[C::PRODUCT_INCLUDED_SANS_WILDCARD] ?: 0,
            C::PRODUCT_ENABLE_SAN_WILDCARD => $input[C::PRODUCT_ENABLE_SAN_WILDCARD] ?: '',
        ];

        if (!empty($input['issued_ssl_message'])) {
            $productData[C::OPTION_ISSUED_SSL_MESSAGE] = $input['issued_ssl_message'];
        }

        if (!empty($input['custom_guide'])) {
            $productData[C::OPTION_CUSTOM_GUIDE] = $input['custom_guide'];
        }

        $productModel = new \AddonModule\RealtimeRegisterSsl\models\productConfiguration\Repository();
        $newProductId = $productModel->createNewProduct($productData);

        $apiProduct = $this->apiProductsRepo->getProduct(KeyToIdMapping::getIdByKey($input[C::API_PRODUCT_ID]));

        self::insertPricing($input[C::API_PRODUCT_ID], $productData['paytype'], $newProductId, $apiProduct->getPeriods());

        if ($apiProduct->isSanEnabled() && $input[C::PRODUCT_ENABLE_SAN] === 'on') {
            ConfigurableOptionService::createForProduct(
                $newProductId,
                $input[C::API_PRODUCT_ID],
                $productData['name'],
                $apiProduct,
                $productData['paytype']
            );
        }

        if ($apiProduct->isSanWildcardEnabled() && $input[C::PRODUCT_ENABLE_SAN_WILDCARD] === 'on') {
            ConfigurableOptionService::createForProductWildCard(
                $newProductId,
                $input[C::API_PRODUCT_ID],
                $productData['name'],
                $apiProduct,
                $productData['paytype']
            );
        }

        return $newProductId;
    }

    /**
     * @throws Exception
     */
    public function saveProducts($currencies, $post)
    {
        if ($post[C::API_PRODUCT_ID]) {
            $apiProducts = [$this->apiProductsRepo->getProduct(KeyToIdMapping::getIdByKey($post[C::API_PRODUCT_ID]))];
        } else {
            $apiProducts = $this->apiProductsRepo->getAllProducts();
        }
        $productModel = new \AddonModule\RealtimeRegisterSsl\models\productConfiguration\Repository();
        $moduleProducts = $productModel->getModuleProducts('realtimeregister_ssl', $post['gid']);
        foreach ($moduleProducts as $moduleProduct) {
            $moduleProductId = KeyToIdMapping::getIdByKey($moduleProduct->configoption1);
            foreach ($apiProducts as $key => $value) {
                if ($moduleProductId == $value->pid) {
                    unset($apiProducts[$key]);
                    break;
                }
            }
        }

        foreach ($apiProducts as $apiProduct) {
            $input = [];
            $input['name'] = $post['name'] ?: self::displayName($apiProduct);
            $input['gid'] = $post['gid'];
            $input[C::PRICE_AUTO_DOWNLOAD] = "true";
            $input[C::API_PRODUCT_ID] = $apiProduct->product;
            $input[C::API_PRODUCT_MONTHS] = $apiProduct->getMaxPeriod();
            $input[C::PRODUCT_ENABLE_SAN] = $apiProduct->isSanEnabled() ? 'on' : '';
            $input[C::PRODUCT_INCLUDED_SANS] = $apiProduct->isSanEnabled() ? $apiProduct->includedDomains : '';
            $input[C::PRODUCT_ENABLE_SAN_WILDCARD] = $apiProduct->isSanWildcardEnabled() ? 'on' : '';
            $input[C::PRODUCT_INCLUDED_SANS_WILDCARD] = $apiProduct->isSanWildcardEnabled() ? 0 : '';
            $input['paytype'] = 'recurring';
            $input['autosetup'] = 'payment';
            $this->saveProduct($input);
        }
    }

    private function insertPricing($apiProductId, $payType, $productId, $periods)
    {
        $priceRepo = new ApiProductPriceRepo();

        $productModel = new \AddonModule\RealtimeRegisterSsl\models\productConfiguration\Repository();
        $currencies = $productModel->getAllCurrencies();
        $defaultCurrency = $currencies->filter(fn($currency) => $currency->default === 1)->first();

        // The prices are sometimes not imported yet, so we force an import when there is no data
        ConfigurableOptionService::loadPrices($priceRepo, $apiProductId);

        $pricing = [
            'relid' => $productId,
            'msetupfee' => '0.00',
            'qsetupfee' => '0.00',
            'ssetupfee' => '0.00',
            'asetupfee' => '0.00',
            'bsetupfee' => '0.00',
            'tsetupfee' => '0.00',
            BillingCycle::MONTHLY => -1.00,
            BillingCycle::QUARTERLY  => '-1.00',
            BillingCycle::SEMI_ANNUALLY => '-1.00',
            BillingCycle::ANNUALLY => -1.00,
            BillingCycle::BIENNIALLY => -1.00,
            BillingCycle::TRIENNIALLY => -1.00
        ];

        foreach ($periods as $period) {
            $price = $priceRepo->onlyApiProductID(KeyToIdMapping::getIdByKey($apiProductId))
                ->onlyPeriod($period)
                ->onlyAction("REQUEST")
                ->fetchOne();
            $basePrice = ConfigurableOptionService::getBasePrice($currencies, $price, $defaultCurrency);

            switch ($period) {
                case 12:
                    if ($payType === BillingCycle::ONE_TIME) {
                        $pricing[BillingCycle::MONTHLY] = $basePrice;
                    }
                    $pricing[BillingCycle::ANNUALLY] = $basePrice;
                    break;
                case 24:
                    $pricing[BillingCycle::BIENNIALLY] = $basePrice;
                    break;
                case 36:
                    $pricing[BillingCycle::TRIENNIALLY] = $basePrice;
                    break;
                default:
                    break;
            }
        }

        foreach ($currencies as $currency) {
            $pricing['currency'] = $currency->id;
            $pricing[BillingCycle::MONTHLY] = $pricing[BillingCycle::MONTHLY] === -1.00
                ? $pricing[BillingCycle::MONTHLY]
                : $pricing[BillingCycle::MONTHLY] * $currency->rate;
            $pricing[BillingCycle::ANNUALLY] = $pricing[BillingCycle::ANNUALLY] === -1.00
                ? $pricing[BillingCycle::ANNUALLY]
                : $pricing[BillingCycle::ANNUALLY] * $currency->rate;
            $pricing[BillingCycle::BIENNIALLY] = $pricing[BillingCycle::BIENNIALLY] === -1.00
                ? $pricing[BillingCycle::BIENNIALLY]
                : $pricing[BillingCycle::BIENNIALLY] * $currency->rate;
            $pricing[BillingCycle::TRIENNIALLY] = $pricing[BillingCycle::TRIENNIALLY] === -1.00
                ? $pricing[BillingCycle::TRIENNIALLY]
                : $pricing[BillingCycle::TRIENNIALLY] * $currency->rate;
            Capsule::table('tblpricing')->insert($pricing);
        }
    }

    public static function displayName($apiProduct)
    {
        switch ($apiProduct->certificateType) {
            case "MULTI_DOMAIN":
                $certificateType = 'Multi Domain';
                break;
            case "WILDCARD":
                $certificateType = 'Wildcard';
                break;
            default:
                $certificateType = 'Single Domain';
                break;
        }
        return $apiProduct->brand . " " . $apiProduct->name . " " .
            $certificateType;
    }

    public function saveItemHTML($input, $vars = [])
    {
        if ($this->checkToken()) {
            try {
                $login = trim($input['login']);
                $password = trim($input['password']);
                if (empty($login) || empty($password)) {
                    throw new Exception('empty_fields');
                }

                $login = $input['login'];
                $password = $input['password'];

                $apiConfigRepo = new Repository();
                $apiConfigRepo->setConfiguration($login, $password);
            } catch (Exception $ex) {
                $vars['formError'] = Lang::T('messages', $ex->getMessage());
            }
        }

        return $this->indexHTML($input, $vars);
    }

    /**
     * This is custom page.
     */
    public function pageHTML(): array
    {
        $vars = [];

        return [
            //You have to create tpl file  /modules/addons/RealtimeRegisterSsl/templates/admin/pages/example1/page.1tpl
            'tpl' => 'page',
            'vars' => $vars
        ];
    }

    /*
     * ************************************************************************
     * AJAX USING ARRAY
     * ************************************************************************
     */

    /**
     * Display custom page for ajax errors
     */
    public function ajaxErrorHTML(): array
    {
        return [
            'tpl' => 'ajaxError'
        ];
    }

    /**
     * Return error message using array
     */
    public function getErrorArrayJSON(): array
    {
        return [
            'error' => 'Custom error'
        ];
    }

    /**
     * Return success message using array
     */
    public function getSuccessArrayJSON(): array
    {
        return [
            'success' => 'Custom success'
        ];
    }

    /*
     * ************************************************************************
     * AJAX USING DATA-ACT
     * ***********************************************************************
     */

    public function ajaxErrorDataActHTML(): array
    {
        return [
            'tpl' => 'ajaxErrorDataAct'
        ];
    }

    /*
     * ************************************************************************
     * AJAX CONTENT
     * ***********************************************************************
     */

    public function ajaxContentHTML(): array
    {
        return [
            'tpl' => 'ajaxContent'
        ];
    }

    public function ajaxContentJSON(): array
    {
        return [
            'html' => Smarty::I()->view('ajaxContentJSON')
        ];
    }

    /*
     * ******************************************************
     * CREATOR
     * *****************************************************
     */

    public function getCreatorJSON()
    {
        $creator = new Popup('mymodal');
        $creator->addField(
            new TextField([
                'name' => 'customTextField',
                'value' => 'empty_value',
                'placeholder' => 'placeholder!'
            ])
        );

        return [
            'modal' => $creator->getHTML()
        ];
    }
}
