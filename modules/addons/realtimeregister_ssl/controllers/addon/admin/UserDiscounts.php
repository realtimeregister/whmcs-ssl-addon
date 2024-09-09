<?php

namespace MGModule\RealtimeRegisterSsl\controllers\addon\admin;

use Illuminate\Database\Capsule\Manager as Capsule;
use MGModule\RealtimeRegisterSsl as main;
use MGModule\RealtimeRegisterSsl\eServices\ConfigurableOptionService;
use MGModule\RealtimeRegisterSsl\models\userDiscount\Repository;

class UserDiscounts extends main\mgLibs\process\AbstractController
{
    /**
     * This is default page.
     */
    public function indexHTML($input = [], $vars = []): array
    {
        //get all clients
        $vars['clients'] = [];

        $clietnsRepo = new \MGModule\RealtimeRegisterSsl\models\whmcs\clients\Clients();
        foreach ($clietnsRepo->get() as $key => $client) {
            $vars['clients'][] = [
                'id' => $client->id,
                'name' => trim($client->firstname . ' ' . $client->lastname . ' ' . $client->companyname),
            ];
        }
        $vars['templatePath1'] = ROOTDIR . DS . 'modules' . DS . 'templates'
            . DS . 'orderforms' . DS . 'YOUR_TEMPLATE' . DS . 'configureproduct.tpl';
        $vars['templatePath2'] = ROOTDIR . DS . 'modules' . DS . 'templates'
            . DS . 'orderforms' . DS . 'YOUR_TEMPLATE' . DS . 'products.tpl';

        return
            [
                'tpl' => 'userDiscounts',
                'vars' => $vars
            ];
    }

    public function addNewDiscountRuleJSON($input = [], $vars = [])
    {
        try {
            if (!isset($input['client_id']) or !trim($input['client_id'])) {
                throw new \Exception(main\mgLibs\Lang::T('messages', 'clientIDNotProvided'));
            }
            if (!isset($input['product_id']) or !trim($input['product_id'])) {
                throw new \Exception(main\mgLibs\Lang::T('messages', 'productIDNotProvided'));
            }
            if (!isset($input['discount']) or !trim($input['discount'])) {
                throw new \Exception(main\mgLibs\Lang::T('messages', 'discountIDNotProvided'));
            }

            $clientID = $input['client_id'];
            $productID = $input['product_id'];
            $discount = $input['discount'];

            $discountRule = new main\models\userDiscount\UserDiscount();
            $discountRule->setClientID($clientID);
            $discountRule->setProductID($productID);
            $discountRule->setPercentage((int) $discount);
            $discountRule->save();
        } catch (\Exception $e) {
            return [
                'error' => true,
                'msg' => $e->getMessage()
            ];
        }

        return [
            'success' => main\mgLibs\Lang::T('messages', 'addSuccess')
        ];
    }

    public function removeDiscountRuleJSON($input = [], $vars = [])
    {
        try {
            if (!isset($input['rule_id']) or !trim($input['rule_id'])) {
                throw new \Exception(main\mgLibs\Lang::T('messages', 'ruleIDNotProvided'));
            }

            $ruleID = $input['rule_id'];

            $discountRule = new main\models\userDiscount\UserDiscount($ruleID);
            $discountRule->delete();
        } catch (\Exception $e) {
            return [
                'error' => true,
                'msg' => $e->getMessage()
            ];
        }

        return [
            'success' => main\mgLibs\Lang::T('messages', 'removeSuccess')
        ];
    }

    public function updateDiscountRuleJSON($input = [], $vars = [])
    {
        try {
            if (!isset($input['rule_id']) || !trim($input['rule_id'])) {
                throw new \Exception(main\mgLibs\Lang::T('messages', 'ruleIDNotProvided'));
            }
            if (!isset($input['discount']) || !trim($input['discount'])) {
                throw new \Exception(main\mgLibs\Lang::T('messages', 'discountIDNotProvided'));
            }

            $ruleID = $input['rule_id'];
            $discount = $input['discount'];

            $discountRule = new main\models\userDiscount\UserDiscount($ruleID);
            $discountRule->setPercentage((int) $discount);
            $discountRule->save();
        } catch (\Exception $e) {
            return [
                'error' => true,
                'msg' => $e->getMessage()
            ];
        }

        return [
            'success' => main\mgLibs\Lang::T('messages', 'updateSuccess')
        ];
    }

    public function getDiscountRulesJSON($input = [], $vars = [])
    {
        try {
            $data['data'] = array();
            $userDiscountRepo = new Repository();

            foreach ($userDiscountRepo->get() as $rule) {
                $data['data'][] = $this->formatRow('row', $rule);
            }
        } catch (\Exception $ex) {
            return [
                'error' => $ex->getMessage()
            ];
        }

        return $data;
    }

    public function getSingleDiscountRuleJSON($input = [], $vars = [])
    {
        try {
            $data = [];
            if (!isset($input['rule_id']) or !trim($input['rule_id'])) {
                throw new \Exception(main\mgLibs\Lang::T('messages', 'ruleIDNotProvided'));
            }

            $ruleID = $input['rule_id'];

            $discountRule = new main\models\userDiscount\UserDiscount($ruleID);
            $data = [
                'client' => $this->getClient($discountRule->getClientID()),
                'product' => $this->getProduct($discountRule->getProductID()),
                'discount' => $discountRule->getPercentage(),
                'pricings' => $this->loadPricing(
                    $discountRule->getProductID(),
                    $discountRule->getPercentage(),
                    true
                )
            ];
        } catch (\Exception $ex) {
            return [
                'error' => $ex->getMessage()
            ];
        }

        return $data;
    }

    public function loadProductPricingJSON($input = [], $vars = [])
    {
        try {
            if (!isset($input['product_id']) or !trim($input['product_id'])) {
                throw new \Exception(main\mgLibs\Lang::T('messages', 'productIDNotProvided'));
            }

            $productID = $input['product_id'];
            $ppricings = [];
            $pricings = $this->getPricings($productID);
            
            foreach ($pricings as $price) {

                $ppricings[] = [
                    'code' => $price->currency,
                    'monthly' => (!in_array($price->monthly, ['-1.00'])) ? $price->monthly : '-',
                    'annually' => (!in_array($price->annually, ['-1.00'])) ? $price->annually : '-',
                    'biennially' => (!in_array($price->biennially, ['-1.00'])) ? $price->biennially : '-',
                    'triennially' => (!in_array($price->triennially, ['-1.00'])) ? $price->triennially : '-',
                ];
            }
        } catch (\Exception $ex) {
            return [
                'error' => $ex->getMessage()
            ];
        }

        return [
            'pricings' => $ppricings
        ];
    }

    public function loadAvailableProductsJSON($input = [], $vars = [])
    {
        try {
            $products = [];
            $productModel = new \MGModule\RealtimeRegisterSsl\models\productConfiguration\Repository();
            //get Realtime Register Ssl all products
            foreach ($productModel->getModuleProducts() as $product) {
                //skip free products
                if ($product->paytype == 'free') {
                    continue;
                }

                //exclude products for which commision is already added for gicen client
                $userDiscountRepo = new \MGModule\RealtimeRegisterSsl\models\userDiscount\Repository();
                $userDiscountRepo->onlyProductID($product->id);

                if (isset($input['client_id'])) {
                    $userDiscountRepo->onlyClientID($input['client_id']);
                }

                if ($userDiscountRepo->count() > 0) {
                    continue;
                }

                $products[] = [
                    'id' => $product->id,
                    'name' => $product->name,
                    'pricings' => $product->pricing
                ];
            }
        } catch (\Exception $ex) {
            return [
                'error' => $ex->getMessage()
            ];
        }

        return [
            'products' => $products
        ];
    }

    private function formatRow($template, $item)
    {
        //get client details
        $client = $this->getClient($item->getClientID());

        //get product details
        $product = $this->getProduct($item->getProductID());
        //load product pricing
        $pricings = $this->loadPricing($item->getProductID(), $item->getPercentage());

        $data['client'] = $client;
        $data['rule_id'] = $item->getID();
        $data['product'] = $product;
        $data['discount'] = $item->getPercentage();
        $data['pricings'] = $pricings;

        $rows = $this->dataTablesParseRow($template, $data);

        return $rows;
    }

    private function getClient($id)
    {
        $clientDetails = new \MGModule\RealtimeRegisterSsl\models\whmcs\clients\Client($id);

        return [
            'id' => $clientDetails->id,
            'name' => trim(
                $clientDetails->firstname . ' ' . $clientDetails->lastname . ' ' . $clientDetails->companyname
            ),
        ];
    }

    private function getProduct($id)
    {
        $productDetails = new \MGModule\RealtimeRegisterSsl\models\whmcs\product\Product($id);

        return [
            'id' => $productDetails->id,
            'name' => $productDetails->name,
        ];
    }

    private function loadPricing($productID, $percentage, $returnNoneIfNotSetOrNull = false)
    {
        $multiplier = (100 - $percentage) / 100;
        $pricings = $this->getPricings($productID);
        foreach ($pricings as &$price) {
            $price->discountMonthly = self::getPrice($price->monthly, $multiplier);
            $price->discountAnnually = self::getPrice($price->annually, $multiplier);
            $price->discountBiennially = self::getPrice($price->biennially, $multiplier);
            $price->discountTriennially = self::getPrice($price->triennially, $multiplier);
            $price->code = Capsule::table("tblcurrencies")
                ->where("id", '=', $price->currency)
                ->first()
                ->code;

            if ($returnNoneIfNotSetOrNull) {
                $price->monthly = self::getPrice($price->monthly);
                $price->annually = self::getPrice($price->annually);
                $price->biennially = self::getPrice($price->biennially);
                $price->triennially = self::getPrice($price->triennially);
            }
        }

        return $pricings;
    }

    private static function getPrice($price, $multiplier = 1) {
        return number_format($price == -1.00 ? 0 : $price * $multiplier, 2);
    }

    /**
     * @param $productID
     * @return \Illuminate\Support\Collection
     */
    public function getPricings($productID): \Illuminate\Support\Collection
    {
        $optionsGroup = ConfigurableOptionService::getForProductPeriod($productID);
        $optionsGroupSub = Capsule::table("tblproductconfigoptionssub")
            ->where("configid", '=', $optionsGroup->id)
            ->where('optionname', '=', '1 years')
            ->first();

        $pricings = Capsule::table('tblpricing')
            ->where("relid", "=", $optionsGroupSub->id)
            ->get();
        return $pricings;
    }
}
