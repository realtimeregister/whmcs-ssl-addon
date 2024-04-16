<?php

namespace MGModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl;

use Illuminate\Database\Capsule\Manager as Capsule;
use Exception;
use MGModule\RealtimeRegisterSsl\eHelpers\Fill;
use MGModule\RealtimeRegisterSsl\eModels\RealtimeRegisterSsl\Product;
use MGModule\RealtimeRegisterSsl\eProviders\ApiProvider;

class Products {
    public const MGFW_REALTIMEREGISTERSSL_PRODUCT_BRAND = 'mgfw_REALTIMEREGISTERSSL_product_brand';

    /**
     *
     * @var Products
     */
    private static $instance;
    
    /**
     *
     * @var Product[]
     */
    private $products;
    
    /**
     * 
     * @return Products
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Products();
        }
        return self::$instance;
    }

    public function getAllProducts() {
        $this->fetchAllProducts();
        return $this->products;
    }

    /**
     * 
     * @param type $id
     * @return Product
     */
    public function getProduct($id) {
        $this->fetchAllProducts();
        if (isset($this->products[$id])) {
            return $this->products[$id];
        }
        return reset($this->products);
    }

    private function fetchAllProducts()
    {
        if ($this->products !== null) {
            return $this->products;
        }

        $checkTable = Capsule::schema()->hasTable(self::MGFW_REALTIMEREGISTERSSL_PRODUCT_BRAND);
        if($checkTable === false)
        {
            Capsule::schema()->create(self::MGFW_REALTIMEREGISTERSSL_PRODUCT_BRAND, function ($table) {
                $table->increments('id');
                $table->integer('pid');
                $table->string('pid_identifier');
                $table->string('brand');
                $table->text('data');
            });
        }
        $checkTable = Capsule::schema()->hasTable(self::MGFW_REALTIMEREGISTERSSL_PRODUCT_BRAND);
        if($checkTable)
        {
            $products = Capsule::table(self::MGFW_REALTIMEREGISTERSSL_PRODUCT_BRAND)->get();
            if(isset($products[0]->id)) {
                $this->products = [];
                foreach ($products as $i => $apiProduct)
                {
                    $apiProduct = json_decode($apiProduct->data, true);
                    $p = new Product();
                    Fill::fill($p, $apiProduct);
                    $p->pid = KeyToIdMapping::getIdByKey($apiProduct['product']);
                    $this->products[$products[$i]->id] = $p;
                }
                return $this->products;

            }
        }

        Capsule::table(self::MGFW_REALTIMEREGISTERSSL_PRODUCT_BRAND)->truncate();
        $this->products = [];

        $i = 0;
        $total = 0;

        while ($apiProducts = ApiProvider::getInstance()->getApi()->getProducts($i)) {
            foreach ($apiProducts['entities'] as $apiProduct) {
                $id = Capsule::table(self::MGFW_REALTIMEREGISTERSSL_PRODUCT_BRAND)->insertGetId([
                    'brand' => $apiProduct['brand'],
                    'pid' => KeyToIdMapping::getIdByKey($apiProduct['product']),
                    'pid_identifier' => $apiProduct['product'],
                    'data' => json_encode($apiProduct)
                ]);
                $p = new Product();
                Fill::fill($p, $apiProduct);
                $this->products[$id] = $p;
            }
            $i +=10;

            $total = $apiProducts['pagination']['total'];
            if ($total < $i) {
                break;
            }
        }

        return $this->products;
    }
}
