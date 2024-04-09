<?php

namespace MGModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl;

use Illuminate\Database\Capsule\Manager as Capsule;
use Exception;

class Products {

    /**
     *
     * @var Products 
     */
    private static $instance;
    
    /**
     *
     * @var \MGModule\RealtimeRegisterSsl\eModels\RealtimeRegisterSsl\Product[]
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
     * @return \MGModule\RealtimeRegisterSsl\eModels\RealtimeRegisterSsl\Product
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

        $checkTable = Capsule::schema()->hasTable('mgfw_REALTIMEREGISTERSSL_product_brand');
        if($checkTable === false)
        {
            Capsule::schema()->create('mgfw_REALTIMEREGISTERSSL_product_brand', function ($table) {
                $table->increments('id');
                $table->string('pid');
                $table->string('brand');
                $table->text('data');
            });
        }
        $checkTable = Capsule::schema()->hasTable('mgfw_REALTIMEREGISTERSSL_product_brand');
        if($checkTable)
        {
            if (Capsule::schema()->hasColumn('mgfw_REALTIMEREGISTERSSL_product_brand', 'data'))
            {
                $products = Capsule::table('mgfw_REALTIMEREGISTERSSL_product_brand')->get();
                if(isset($products[0]->id)) {
                    $this->products = [];
                    foreach ($products as $i => $apiProduct)
                    {
                        $apiProduct = json_decode($apiProduct->data, true);
                        $p = new \MGModule\RealtimeRegisterSsl\eModels\RealtimeRegisterSsl\Product();
                        \MGModule\RealtimeRegisterSsl\eHelpers\Fill::fill($p, $apiProduct);
                        $p->pid = $apiProduct['product'];

                        $this->products[$products[$i]->id] = $p;
                    }
                    return $this->products;
                
                }
                
            }
        }

        Capsule::table('mgfw_REALTIMEREGISTERSSL_product_brand')->truncate();
        $this->products = [];

        $i = 0;
        $total = 0;

        while ($apiProducts = \MGModule\RealtimeRegisterSsl\eProviders\ApiProvider::getInstance()->getApi()->getProducts($i)) {
            foreach ($apiProducts['entities'] as $apiProduct) {
                $id = Capsule::table('mgfw_REALTIMEREGISTERSSL_product_brand')->insertGetId([
                    'brand' => $apiProduct['brand'],
                    'pid' => $apiProduct['product'],
                    'data' => json_encode($apiProduct)
                ]);
                $p = new \MGModule\RealtimeRegisterSsl\eModels\RealtimeRegisterSsl\Product();
                \MGModule\RealtimeRegisterSsl\eHelpers\Fill::fill($p, $apiProduct);
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
