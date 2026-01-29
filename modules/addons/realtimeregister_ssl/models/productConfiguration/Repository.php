<?php

namespace AddonModule\RealtimeRegisterSsl\models\productConfiguration;

use AddonModule\RealtimeRegisterSsl\eServices\provisioning\ConfigOptions as C;
use Illuminate\Database\Capsule\Manager as Capsule;

class Repository extends \AddonModule\RealtimeRegisterSsl\addonLibs\models\Repository
{

    private static string $TABLE_NAME = 'tblproducts';

    public function getModelClass()
    {
        return __NAMESPACE__ . '\ProductConfigurationItem';
    }

    public function get()
    {
        return Capsule::table(self::$TABLE_NAME)->first();
    }

    public function getById($productId)
    {
        return Capsule::table(self::$TABLE_NAME)->where('id', '=', $productId)->first();
    }

    public function getModuleProducts($moduleName = "realtimeregister_ssl", $gid = 0)
    {
        if (empty($moduleName)) {
            return false;
        }

        $products = Capsule::table("tblproducts")
                ->where("tblproducts.servertype", "=", $moduleName);
           
        if ($gid) {
            $products = $products->where("tblproducts.gid", "=", $gid);
        }
        
        $products = $products->get();
        $allPricingDB = $this->getAllProductPricing();

        foreach ($products as $key => $value) {
            foreach($allPricingDB as $sp) {
                if ($sp->relid == $value->id && $sp->type == 'product') {
                    $products[$key]->pricing[] = $sp;
                }
            }
         }

        return $products;
    }
    
    public function getSelectedProducts($ids)
    {
        $products = $this->getModuleProducts();
        
        foreach($products as $key => $value) {
            if(!in_array($value->id, $ids)) {
                unset($products[$key]);
            }
        }
        
        return $products;
    }
    
    public function updateProductParam($productId, $paramName, $value)
    {
        Capsule::table('tblproducts')->where('id', $productId)->update([
            $paramName => $value
        ]);
        
        return true;
    }

    public function getAllCurrencies()
    {
        return Capsule::table("tblcurrencies")->get();
    }

    public function getProductPricing($productId)
    {
        return Capsule::table("tblpricing")
            ->select('*', 'tblpricing.id as pricing_id')
            ->join('tblcurrencies', 'tblcurrencies.id', '=', 'tblpricing.currency')
            ->where("tblpricing.relid", "=", $productId)
            ->where("tblpricing.type", "=", 'product')
            ->orderBy('tblcurrencies.code', 'ASC')
            ->get();
    }
    
    public function getAllProductPricing()
    {
        return Capsule::table("tblpricing")
            ->select('*', 'tblpricing.id as pricing_id')
            ->join('tblcurrencies', 'tblcurrencies.id', '=', 'tblpricing.currency')
            ->where("tblpricing.type", "=", 'product')
            ->orderBy('tblcurrencies.code', 'ASC')
            ->get();
    }
    
    public function enableProduct($productId)
    {
        return Capsule::table('tblproducts')->where('id', $productId)
            ->update(['hidden' => 0]
        );
    }

    public function disableProduct($productId)
    {
        return Capsule::table('tblproducts')->where('id', $productId)
            ->update(['hidden' => 1]
        );
    }

    public function updateProductName($productId, $name)
    {
        return Capsule::table('tblproducts')->where('id', $productId)
            ->update(
                [
                    'name' => $name,
                    'paytype' => 'recurring'
                ]
        );
    }
    
    public function updateProductDetails($productId, $params)
    {
        $update                           = [];
        $update['name']                   = $params['name'];
        $update[C::PRODUCT_ENABLE_SAN]    = $params[C::PRODUCT_ENABLE_SAN] ?: '';
        $update[C::PRODUCT_ENABLE_SAN_WILDCARD]    = $params[C::PRODUCT_ENABLE_SAN_WILDCARD] ?: '';
        $update[C::PRODUCT_INCLUDED_SANS] = $params[C::PRODUCT_INCLUDED_SANS] ?: '0';
        $update[C::PRODUCT_INCLUDED_SANS_WILDCARD] = $params[C::PRODUCT_INCLUDED_SANS_WILDCARD] ?: '0';
        $update['paytype']                = $params['paytype'];
        $update['autosetup']              = $params['autosetup'];
        $update[C::PRICE_AUTO_DOWNLOAD]   = $params[C::PRICE_AUTO_DOWNLOAD] ?: '0';
        $update[C::AUTH_KEY_ENABLED]      = $params[C::AUTH_KEY_ENABLED] ?: '0';
        $update[C::COMMISSION]            = $params[C::COMMISSION] ? $params[C::COMMISSION] / 100 : '0';

        
        if (isset($params['issued_ssl_message']) && !empty($params['issued_ssl_message'])) {
            $update[C::OPTION_ISSUED_SSL_MESSAGE] = $params['issued_ssl_message'];
        }

        if (isset($params['custom_guide']) && !empty($params['custom_guide'])) {
            $update[C::OPTION_CUSTOM_GUIDE] = $params['custom_guide'];
        }
        
        return Capsule::table('tblproducts')->where('id', $productId)->update($update);
    }

    public function updateProductPricing($pricingId, $data)
    {
        return Capsule::table('tblpricing')->where('id', $pricingId)->update($data);
    }

    public function createNewProduct($productData)
    {
        return Capsule::table('tblproducts')->insertGetId($productData);
    }

    public function createPricing($pricingData)
    {
        return Capsule::table('tblpricing')->insertGetId($pricingData);
    }

    public function parseProductsForTable($products)
    {
    }

    public function dropProducts()
    {
        return Capsule::table('tblproducts')
            ->where('servertype', 'realtimeregister_ssl')
            ->delete();
    }
}
