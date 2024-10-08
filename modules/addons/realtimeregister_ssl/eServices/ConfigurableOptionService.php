<?php

namespace AddonModule\RealtimeRegisterSsl\eServices;

use Illuminate\Database\Capsule\Manager as Capsule;
use AddonModule\RealtimeRegisterSsl\eHelpers\Whmcs;
use AddonModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\KeyToIdMapping;
use AddonModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\ProductsPrices;
use AddonModule\RealtimeRegisterSsl\eServices\provisioning\ConfigOptions;
use AddonModule\RealtimeRegisterSsl\models\productPrice\Repository as ApiProductPriceRepo;

class ConfigurableOptionService
{
    public static function getForProduct($productId, $name = 'sans_count')
    {
        return Capsule::table('tblproductconfiggroups')
            ->select(['tblproductconfigoptions.id', 'tblproductconfiggroups.id as groupid'])
            ->join('tblproductconfigoptions', 'tblproductconfigoptions.gid', '=', 'tblproductconfiggroups.id')
            ->where(
                'tblproductconfiggroups.description',
                '=',
                'Auto generated by module - RealtimeRegisterSSL #' . $productId
            )
            ->where('tblproductconfigoptions.optionname', 'LIKE', $name . '%')
            ->get();
    }

    public static function getForProductWildcard($productId)
    {
        return Capsule::table('tblproductconfiggroups')
            ->select(['tblproductconfigoptions.id', 'tblproductconfiggroups.id as groupid'])
            ->join('tblproductconfigoptions', 'tblproductconfigoptions.gid', '=', 'tblproductconfiggroups.id')
            ->where(
                'tblproductconfiggroups.description',
                '=',
                'Auto generated by module - RealtimeRegisterSSL #' . $productId
            )
            ->where('tblproductconfigoptions.optionname', 'LIKE', 'sans_wildcard_count%')
            ->get();
    }

    public static function getForProductPeriod($productId)
    {
        return Capsule::table('tblproductconfiggroups')
            ->select(['tblproductconfigoptions.id', 'tblproductconfiggroups.id as groupid'])
            ->join('tblproductconfigoptions', 'tblproductconfigoptions.gid', '=', 'tblproductconfiggroups.id')
            ->where(
                'tblproductconfiggroups.description',
                '=',
                'Auto generated by module - RealtimeRegisterSSL #' . $productId
            )
            ->where('tblproductconfigoptions.optionname', 'LIKE', 'years%')
            ->first();
    }

    public static function unassignFromProduct($productId, $name)
    {
        $optionGroup = [
            'name' => 'RealtimeRegisterSsl - ' . $name,
            'description' => 'Auto generated by module - RealtimeRegisterSSL #' . $productId
        ];

        $optionGroup = Capsule::table('tblproductconfiggroups')
            ->select('id')
            ->where('name', '=', $optionGroup['name'])
            ->where('description', '=', $optionGroup['description'])
            ->first();

        $optionLink = [
            'gid' => $optionGroup->id,
            'pid' => $productId
        ];

        Capsule::table('tblproductconfiglinks')
            ->where('gid', '=', $optionLink['gid'])
            ->where('pid', '=', $optionLink['pid'])
            ->delete();
    }

    public static function assignToProduct($productId, $name)
    {
        $optionGroup = [
            'name' => 'RealtimeRegisterSsl - ' . $name,
            'description' => 'Auto generated by module - RealtimeRegisterSSL #' . $productId
        ];

        $optionGroupResult = Capsule::table('tblproductconfiggroups')
            ->select('id')
            ->where('name', '=', $optionGroup['name'])
            ->where('description', '=', $optionGroup['description'])
            ->first();

        if ($optionGroupResult === null) {
            self::createForProduct($productId, $name);
            $optionGroupResult = self::getForProduct($productId);
        }

        $count = Capsule::table('tblproductconfiglinks')
            ->where('gid', '=', $optionGroupResult->id)
            ->where('pid', '=', $productId)
            ->count();

        if (!$count) {
            $optionLink = [
                'gid' => $optionGroupResult->id,
                'pid' => $productId
            ];
            Capsule::table('tblproductconfiglinks')->insert($optionLink);
        }
    }

    public static function unassignFromProductWildcard($productId, $groupid)
    {
        $optionGroup = Capsule::table('tblproductconfiggroups')
            ->select('id')
            ->where('id', '=', $groupid)
            ->first();

        $optionLink = [
            'gid' => $optionGroup->id,
            'pid' => $productId
        ];

        Capsule::table('tblproductconfiglinks')
            ->where('gid', '=', $optionLink['gid'])
            ->where('pid', '=', $optionLink['pid'])
            ->delete();
    }

    public static function assignToProductWildcard($productId, $name, $groupid)
    {
        $optionGroupResult = Capsule::table('tblproductconfiggroups')
            ->select('id')
            ->where('id', '=', $groupid)
            ->first();

        if ($optionGroupResult === null) {
            self::createForProduct($productId, $name);
            $optionGroupResult = self::getForProductWildcard($productId);
        }

        $count = Capsule::table('tblproductconfiglinks')
            ->where('gid', '=', $optionGroupResult->id)
            ->where('pid', '=', $productId)
            ->count();

        if (!$count) {
            $optionLink = [
                'gid' => $optionGroupResult->id,
                'pid' => $productId
            ];
            Capsule::table('tblproductconfiglinks')->insert($optionLink);
        }
    }

    public static function createForProduct($productId, $apiProductId, $name, $apiProduct)
    {
        if (!self::getForProduct($productId)->isEmpty()) {
            return null;
        }

        $optionGroupId = self::getOptionGroup($name, $productId);

        self::insertOptions($apiProductId, $apiProduct, [
            "optionGroupId" => $optionGroupId,
            "optionName" => provisioning\ConfigOptions::OPTION_SANS_COUNT . "%s|Additional Single domain SANs (%s)",
            "action" => "EXTRA_DOMAIN",
            "maximum" => $apiProduct->getMaxDomains() - $apiProduct->getIncludedDomains()
        ]);
    }

    public static function createForProductWildCard($productId, $apiProductId, $name, $apiProduct)
    {
        if (!self::getForProduct($productId, 'wildcard_sans_count')->isEmpty()) {
            return;
        }

        $optionGroupId = self::getOptionGroup($name, $productId);

        self::insertOptions($apiProductId, $apiProduct, [
            "optionGroupId" => $optionGroupId,
            "optionName" => provisioning\ConfigOptions::OPTION_SANS_WILDCARD_COUNT
                . "%s|Additional Wildcard domain SANs(%s)",
            "action" => "EXTRA_WILDCARD",
            "maximum" => $apiProduct->getMaxDomains() - $apiProduct->getIncludedDomains()
        ]);
    }

    public static function insertPeriods($productId, $apiProductId, $name, array $periods)
    {
        if (!self::getForProduct($productId, 'years')->isEmpty()) {
            return;
        }

        $optionGroupId = self::getOptionGroup($name, $productId);
        $option = [
            'gid' => $optionGroupId,
            'optionname' => "years|Years",
            'optiontype' => 1,
            'order' => 1,
            'hidden' => 0,
        ];
        $optionId = Capsule::table('tblproductconfigoptions')->insertGetId($option);

        sort($periods);

        $priceRepo = new ApiProductPriceRepo();

        $productModel = new \AddonModule\RealtimeRegisterSsl\models\productConfiguration\Repository();
        $currencies = $productModel->getAllCurrencies();
        $defaultCurrency = $currencies->filter(fn($currency) => $currency->default === 1)->first();

        // The prices are sometimes not imported yet, so we force an import when there is no data
        self::loadPrices($priceRepo, $apiProductId);

        foreach($periods as $i => $period) {
            $price = $priceRepo->onlyApiProductID(KeyToIdMapping::getIdByKey($apiProductId))
                    ->onlyPeriod($period)
                    ->onlyAction("REQUEST")
                    ->fetchOne();

            $optionsSub = [
                'configid' => $optionId,
                'optionname' => $period / 12 . ' years',
                'sortorder' => $i,
                'hidden' => 0,
            ];
            $optionSubId = Capsule::table('tblproductconfigoptionssub')->insertGetId($optionsSub);
            $basePrice = self::getBasePrice($currencies, $price, $defaultCurrency);

            $optionSubPrice = [
                'type' => 'configoptions',
                'currency' => 'xxxx',
                'relid' => $optionSubId,
                'msetupfee' => '0.00',
                'qsetupfee' => '0.00',
                'ssetupfee' => '0.00',
                'asetupfee' => '0.00',
                'bsetupfee' => '0.00',
                'tsetupfee' => '0.00',
                'monthly' => '0.00',
                'quarterly' => '0.00',
                'semiannually' => '0.00',
                'annually' => '0.00',
                'biennially' => '0.00',
                'triennially' => '0.00',
            ];

            foreach ($productModel->getAllCurrencies() as $currency) {
                $optionSubPrice['currency'] = $currency->id;
                $optionSubPrice['monthly'] = $basePrice * $currency->rate;
                Capsule::table('tblpricing')->insertGetId($optionSubPrice);
            }
        }
    }

    private static function getOptionGroup($name, $productId) : int {
        $optionGroupResult = Capsule::table('tblproductconfiggroups')
            ->select('id')
            ->where('name', '=', 'RealtimeRegisterSsl - ' . $name)
            ->where('description', '=', 'Auto generated by module - RealtimeRegisterSSL #' . $productId)
            ->first();

        if ($optionGroupResult != null) {
            return $optionGroupResult->id;
        }
        $optionGroup = [
            'name' => 'RealtimeRegisterSsl - ' . $name,
            'description' => 'Auto generated by module - RealtimeRegisterSSL #' . $productId
        ];
        $optionGroupId = Capsule::table('tblproductconfiggroups')->insertGetId($optionGroup);

        $optionLink = [
            'gid' => $optionGroupId,
            'pid' => $productId
        ];
        Capsule::table('tblproductconfiglinks')->insert($optionLink);
        return $optionGroupId;
    }

    public static function generateNewPricesBasedOnCommission($commission, $product)
    {
        $optionGroupResult = Capsule::table('tblproductconfiggroups')
            ->select('id')
            ->where('description', '=', 'Auto generated by module - RealtimeRegisterSSL #' . $product->id)
            ->first();

        if ($optionGroupResult == null) {
            return;
        }

        $configOptions = Capsule::table('tblproductconfigoptions')
            ->select()
            ->where('gid', '=', $optionGroupResult->id)
            ->get();

        foreach($configOptions as $configOption) {
            $configOptionSubs = Capsule::table('tblproductconfigoptionssub')
                ->select()
                ->where('configid', '=', $configOption->id)
                ->orderBy('sortorder')
                ->get();

            foreach ($configOptionSubs as $configOptionSub) {
                $pricings = Capsule::table('tblpricing')
                    ->where('relid', '=', $configOptionSub->id)
                    ->get()
                    ->toArray();

                foreach ($pricings as $pricing) {
                    Capsule::table('tblpricing')
                        ->where('id', '=', $pricing->id)
                        ->update([
                            "msetupfee" => self::updatePrice($pricing->msetupfee, $product, $commission),
                            "asetupfee" => self::updatePrice($pricing->asetupfee, $product, $commission),
                            "bsetupfee" => self::updatePrice($pricing->bsetupfee, $product, $commission),
                            "tsetupfee" => self::updatePrice($pricing->tsetupfee, $product, $commission),
                            "monthly" => self::updatePrice($pricing->monthly, $product, $commission),
                            "annually" => self::updatePrice($pricing->annually, $product, $commission),
                            "biennially" => self::updatePrice($pricing->biennially, $product, $commission),
                            "triennially" => self::updatePrice($pricing->triennially, $product, $commission),
                        ]);
                }
            }
        }
    }

    private static function updatePrice($price, $product, $commission) {
        $oldCommission = $product->{ConfigOptions::COMMISSION} === '' ? 0 : $product->{ConfigOptions::COMMISSION};
        return $price == -1.00 ? $price : $price / (1 + $oldCommission) * (1 + $commission);
    }

    private static function insertOptions($apiProductId, $apiProduct, array $options) {
        $periods = $apiProduct->getPeriods();
        $productModel = new \AddonModule\RealtimeRegisterSsl\models\productConfiguration\Repository();

        sort($periods);

        $priceRepo = new ApiProductPriceRepo();

        // The prices are sometimes not imported yet, so we force an import when there is no data
        self::loadPrices($priceRepo, $apiProductId);

        $currencies = $productModel->getAllCurrencies();
        $defaultCurrency = $currencies->filter(fn($currency) => $currency->default === 1)->first();

        foreach($periods as $i => $period) {
            $years = $period / 12;
            $price = $priceRepo->onlyApiProductID(KeyToIdMapping::getIdByKey($apiProductId))
                    ->onlyPeriod($period)
                    ->onlyAction($options['action'])
                    ->fetchOne();
            $basePrice = self::getBasePrice($currencies, $price, $defaultCurrency);
            $option = [
                'gid' => $options['optionGroupId'],
                'optionname' => sprintf($options['optionName'], $years, $years . ' years'),
                'optiontype' => 4,
                'qtyminimum' => 0,
                'qtymaximum' => $options['maximum'],
                'order' => $i + count($periods),
                'hidden' => 0,
            ];
            $optionId = Capsule::table('tblproductconfigoptions')->insertGetId($option);

            $optionsSub = [
                'configid' => $optionId,
                'optionname' => 'SAN',
                'sortorder' => 0,
                'hidden' => 0,
            ];
            $optionSubId = Capsule::table('tblproductconfigoptionssub')->insertGetId($optionsSub);

            $optionSubPrice = [
                'type' => 'configoptions',
                'currency' => 'xxxx',
                'relid' => $optionSubId,
                'msetupfee' => '0.00',
                'qsetupfee' => '0.00',
                'ssetupfee' => '0.00',
                'asetupfee' => '0.00',
                'bsetupfee' => '0.00',
                'tsetupfee' => '0.00',
                'monthly' => '0.00',
                'quarterly' => '0.00',
                'semiannually' => '0.00',
                'annually' => '0.00',
                'biennially' => '0.00',
                'triennially' => '0.00',
            ];

            foreach ($productModel->getAllCurrencies() as $currency) {
                $optionSubPrice['currency'] = $currency->id;
                $optionSubPrice['monthly'] = $basePrice * $currency->rate;
                Capsule::table('tblpricing')->insertGetId($optionSubPrice);
            }
        }
    }

    /**
     * @param ApiProductPriceRepo $priceRepo
     * @param $apiProductId
     * @return void
     */
    public static function loadPrices(ApiProductPriceRepo $priceRepo, $apiProductId): void
    {
        try {
            $priceRepo->onlyApiProductID(KeyToIdMapping::getIdByKey($apiProductId))->fetchOne();
        } catch (\Exception $e) {
            Whmcs::savelogActivityRealtimeRegisterSsl("Realtime Register SSL WHMCS: loaded prices because they weren't available.");

            $apiProductsPrices = ProductsPrices::getInstance();
            foreach ($apiProductsPrices->getAllProductsPrices() as $productPrice) {
                $productPrice->saveToDatabase();
            }
        }
    }

    public static function getConfigOptionById($optionId) {
        return Capsule::table("tblproductconfigoptions")
            ->where("id", "=", $optionId)
            ->first();
    }

    public static function getConfigOptionSubByOptionId($optionId) {
        return Capsule::table("tblproductconfigoptionssub")
            ->where("configid", "=", $optionId)
            ->first();
    }

    public static function getBasePrice($currencies, $apiPrice, $defaultCurrency) {
        if ($apiPrice->currency !== $defaultCurrency->code) {
            $currency = $currencies->filter(function($currency) use ($apiPrice) {
                return $apiPrice->currency === $currency->code;
            })->first();
            if ($currency === null) {
                return -1.00;
            }
            return $apiPrice->price / $currency->rate / 100;
        } else {
            return $apiPrice->price / 100;
        }
    }
}
