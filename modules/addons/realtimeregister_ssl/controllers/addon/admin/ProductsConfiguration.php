<?php

namespace AddonModule\RealtimeRegisterSsl\controllers\addon\admin;

use AddonModule\RealtimeRegisterSsl\addonLibs\forms\Popup;
use AddonModule\RealtimeRegisterSsl\addonLibs\Lang;
use AddonModule\RealtimeRegisterSsl\addonLibs\process\AbstractController;
use AddonModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\KeyToIdMapping;
use AddonModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\Products;
use AddonModule\RealtimeRegisterSsl\eServices\ConfigurableOptionService;
use AddonModule\RealtimeRegisterSsl\eServices\provisioning\ConfigOptions as C;
use AddonModule\RealtimeRegisterSsl\models\productConfiguration\Repository as ConfigRepo;
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
            $productModel = new ConfigRepo();
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
                $apiConfig->isAuthKeyEnabled = $apiProduct->isAuthKeyEnabled();
                $products[$key]->apiConfig = $apiConfig;
                $products[$key]->confOption = ConfigurableOptionService::getForProduct($product->id)[0];
                $products[$key]->confOptionWildcard = ConfigurableOptionService::getForProductWildcard($product->id)[0];
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
        $productModel = new ConfigRepo();
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

        $product = array_pop($input['product']);

        foreach ($input['currency'] as $key => $value) {
            if ($product['paytype'] == 'recurring') {
                $value['monthly'] = '-1.00';
            } else {
                $value['monthly'] = $value['monthly'] ?? $value['annually'];
            }
            $productModel->updateProductPricing($key, $value);
        }

        $currentProduct = $productModel->getById($product['id']);
        $productModel->updateProductDetails($product['id'], $product);
        $this->recalculatePrices(
            $currentProduct,
            $product[C::COMMISSION] ? $product[C::COMMISSION] / 100 : 0
        );

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

            $productModel = new ConfigRepo();
            if ($productModel->enableProduct($productId)) {
                return [
                    'success' => ""
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

            $productModel = new ConfigRepo();
            if ($productModel->disableProduct($productId)) {
                return [
                    'success' => ""
                ];
            }
        }

        return [
            'error' => Lang::T('messages', '')
        ];
    }
}
