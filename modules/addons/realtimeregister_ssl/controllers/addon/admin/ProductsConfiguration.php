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
use Exception;

/*
 * Base example
 */
class ProductsConfiguration extends AbstractController
{
    /**
     * This is default page.
     */
    public function indexHTML($input = [], $vars = []): array
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] === 'POST' and isset($input['saveProduct'])) {
                $ajax = false;

                if (isset($input['ajax']) && $input['ajax'] == '1') {
                    $ajax = true;
                    $tempArray = [];
                    parse_str($input['field'], $output);
                    foreach ($output as $key => $value) {
                        $tempArray[str_replace('amp;', '', $key)] = $value;
                    }
                    $tempArray['saveProduct'] = 'Save';

                    unset($input['field']);
                    unset($input['ajax']);

                    $input = array_merge($input, $tempArray);
                }

                $this->saveProducts($input, $vars);

                if ($ajax) {
                    die('ok');
                }

                $vars['success'] = Lang::T('messages', 'product_saved');
            }
            $productModel = new \AddonModule\RealtimeRegisterSsl\models\productConfiguration\Repository();
            $products = $productModel->getModuleProducts();
            foreach ($products as $key => $product) {
                try {
                    $apiProduct = Products::getInstance()->getProduct(KeyToIdMapping::getIdByKey($product->{C::API_PRODUCT_ID}));
                } catch (Exception $e) {
                    unset($products[$key]);
                    continue;
                }

                $apiConfig = (object)null;
                $apiConfig->name = $apiProduct->product;
                $apiConfig->periods = $apiProduct->max_period;
                $apiConfig->availablePeriods = $apiProduct->getPeriods();
                $apiConfig->isSanEnabled = $apiProduct->isSanEnabled();
                $apiConfig->isWildcardSanEnabled = $apiProduct->isSanWildcardEnabled();
                $products[$key]->apiConfig = $apiConfig;
                $products[$key]->confOption = ConfigurableOptionService::getForProduct($product->id)->toArray();
                $products[$key]->confOptionWildcard = ConfigurableOptionService::getForProductWildcard($product->id)->toArray();
            }

            $vars['products'] = $products;
            $vars['products_count'] = count($vars['products']);

            if (!empty($apiProducts['products']) && is_array($apiProducts['products'])) {
                $vars['apiProducts'] = $apiProducts['products'];
            }

            $vars['form'] = '';
        } catch (Exception $e) {
            $vars['formError'] = Lang::T('messages', $e->getMessage());
        }

        return [
            'tpl' => 'products_configuration',
            'vars' => $vars
        ];
    }

    public function saveProducts($input = [], $vars = [])
    {
        $productModel = new \AddonModule\RealtimeRegisterSsl\models\productConfiguration\Repository();
        if (isset($input['many-products']) && $input['many-products'] == '1') {
            $products = [];

            switch ($input['type']) {
                case 'all':
                    $products = $productModel->getModuleProducts();
                    break;
                case 'selected':
                    $products = $productModel->getSelectedProducts($input['products']);
                    break;
            }

            foreach ($products as $product) {
                if (isset($input['autosetup']) && $input['autosetup'] != 'donot') {
                    $productModel->updateProductParam($product->id, 'autosetup', $input['autosetup']);
                }
                if (isset($input[C::COMMISSION]) && !empty($input[C::COMMISSION])) {
                    $commission = ($input[C::COMMISSION] / 100);
                    $productModel->updateProductParam($product->id, C::COMMISSION, $commission);
                }
                if (isset($input['hidden']) && $input['hidden'] == '1') {
                    $productModel->updateProductParam($product->id, 'hidden', '0');
                }
                if (isset($input[C::PRICE_AUTO_DOWNLOAD]) && $input[C::PRICE_AUTO_DOWNLOAD] == '1') {
                    $productModel->updateProductParam(
                        $product->id,
                        C::PRICE_AUTO_DOWNLOAD,
                        $input[C::PRICE_AUTO_DOWNLOAD]
                    );
                }

                if (
                    isset(
                        $input[C::PRODUCT_INCLUDED_SANS_WILDCARD]) && !empty($input[C::PRODUCT_INCLUDED_SANS_WILDCARD]
                    )
                ) {
                    $productModel->updateProductParam(
                        $product->id,
                        C::PRODUCT_INCLUDED_SANS_WILDCARD,
                        $input[C::PRODUCT_INCLUDED_SANS_WILDCARD]
                    );
                }

                if (isset($input['issued_ssl_message']) && !empty($input['issued_ssl_message'])) {
                    $productModel->updateProductParam($product->id, C::OPTION_ISSUED_SSL_MESSAGE, $input['issued_ssl_message']);
                }

                if (isset($input['custom_guide']) && !empty($input['custom_guide'])) {
                    $productModel->updateProductParam($product->id, C::OPTION_CUSTOM_GUIDE, $input['custom_guide']);
                }
            }

            return true;
        }

        foreach ($input['product'] as $key => $value) {
            $product = $productModel->getById($key);
            $productModel->updateProductDetails($key, $value);
            $this->recalculatePrices(
                $product,
                $value[C::COMMISSION] ? $value[C::COMMISSION] / 100 : 0
            );
        }

        foreach ($input['currency'] as $key => $value) {
            $productModel->updateProductPricing($key, $value);
        }

        return true;
    }

    private function recalculatePrices($product, $commission) {
        if ($product->{C::COMMISSION} == $commission) {
            return;
        }

        ConfigurableOptionService::generateNewPricesBasedOnCommission($commission, $product);
    }

    public function enableProductJSON($input, $vars = [])
    {
        $productId = trim($input['productId']);
        if (!empty($productId)) {
            $productId = trim($input['productId']);

            $productModel = new \AddonModule\RealtimeRegisterSsl\models\productConfiguration\Repository();
            if ($productModel->enableProduct($productId)) {
                return [
                    'success' => Lang::T('messages', '')
                ];
            }
        }

        return [
            'error' => Lang::T('messages', '')
        ];
    }

    public function disableProductJSON($input, $vars = [])
    {
        $productId = trim($input['productId']);
        if (!empty($productId)) {
            $productId = trim($input['productId']);

            $productModel = new \AddonModule\RealtimeRegisterSsl\models\productConfiguration\Repository();
            if ($productModel->disableProduct($productId)) {
                return [
                    'success' => Lang::T('messages', '')
                ];
            }
        }

        return [
            'error' => Lang::T('messages', '')
        ];
    }

    function saveItemHTML($input, $vars = [])
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
     * *********************************************************************** */

    public function ajaxContentHTML()
    {
        return [
            'tpl' => 'ajaxContent'
        ];
    }

    public function ajaxContentJSON()
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
    public function getCreatorJSON(): array
    {
        $creator = new Popup('mymodal');
        $creator->addField(new TextField([
            'name' => 'customTextField',
            'value' => 'empty_value',
            'placeholder' => 'placeholder!'
        ]));

        return [
            'modal' => $creator->getHTML()
        ];
    }
}
