<?php

namespace AddonModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl;

use AddonModule\RealtimeRegisterSsl\eHelpers\Fill;
use AddonModule\RealtimeRegisterSsl\eModels\RealtimeRegisterSsl\Product;
use AddonModule\RealtimeRegisterSsl\eProviders\ApiProvider;
use Illuminate\Database\Capsule\Manager as Capsule;
use RealtimeRegister\Api\CertificatesApi;

class Products
{
    public const REALTIMEREGISTERSSL_PRODUCT_BRAND = 'REALTIMEREGISTERSSL_product_brand';

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
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Products();
        }
        return self::$instance;
    }

    public function getAllProducts()
    {
        $this->fetchAllProducts();
        return $this->products;
    }

    /**
     * @return Product
     */
    public function getProduct(int $id)
    {
        $this->fetchAllProducts();

        /** @var Product $product */
        foreach ($this->products as $product) {
            if ($product->pid == $id) {
                return $product;
            }
        }

        return reset($this->products);
    }

    public function getProductByName(string $productName)
    {
        $this->fetchAllProducts();

        /** @var Product $product */
        foreach ($this->products as $product) {
            if ($product->product == $productName) {
                return $product;
            }
        }

        return reset($this->products);
    }

    private function fetchAllProducts()
    {
        if ($this->products !== null) {
            return $this->products;
        }

        $checkTable = Capsule::schema()->hasTable(self::REALTIMEREGISTERSSL_PRODUCT_BRAND);
        if ($checkTable === false) {
            Capsule::schema()->create(self::REALTIMEREGISTERSSL_PRODUCT_BRAND, function ($table) {
                $table->increments('id');
                $table->integer('pid');
                $table->string('pid_identifier');
                $table->string('brand');
                $table->text('data');
            });
        }
        $checkTable = Capsule::schema()->hasTable(self::REALTIMEREGISTERSSL_PRODUCT_BRAND);
        if ($checkTable) {
            $products = Capsule::table(self::REALTIMEREGISTERSSL_PRODUCT_BRAND)->get();
            if (isset($products[0]->id)) {
                $this->products = [];
                foreach ($products as $i => $apiProduct) {
                    $apiProduct = json_decode($apiProduct->data, true);
                    $p = new Product();
                    Fill::fill($p, $apiProduct);
                    $p->pid = KeyToIdMapping::getIdByKey($apiProduct['product']);
                    $this->products[$products[$i]->id] = $p;
                }
                return $this->products;
            }
        }

        Capsule::table(self::REALTIMEREGISTERSSL_PRODUCT_BRAND)->truncate();
        $this->products = [];

        $i = 0;

        /** @var CertificatesApi $certificatedApi */
        $certificatedApi = ApiProvider::getInstance()->getApi(CertificatesApi::class);
        while ($apiProducts = $certificatedApi->listProducts(10, $i)) {
            /** @var \RealtimeRegister\Domain\Product $apiProduct */
            foreach ($apiProducts->toArray() as $apiProduct) {
                Capsule::table(Products::REALTIMEREGISTERSSL_PRODUCT_BRAND)->insert([
                    'pid' => KeyToIdMapping::getIdByKey($apiProduct['product']),
                    'pid_identifier' => $apiProduct['product'],
                    'brand' => $apiProduct['brand'],
                    'data' => json_encode($apiProduct)
                ]);
            }
            $i +=10;

            if ($apiProducts->pagination->total < $i) {
                break;
            }
        }

        return $this->products;
    }

    public static function dropTable() {
        Capsule::schema()->dropIfExists(self::REALTIMEREGISTERSSL_PRODUCT_BRAND);
    }
}
