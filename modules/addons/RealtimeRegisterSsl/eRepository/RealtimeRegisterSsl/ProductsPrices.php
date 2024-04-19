<?php

namespace MGModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl;

use MGModule\RealtimeRegisterSsl\eHelpers\Fill;
use MGModule\RealtimeRegisterSsl\eModels\RealtimeRegisterSsl\ProductPrice;
use MGModule\RealtimeRegisterSsl\eProviders\ApiProvider;
use SandwaveIo\RealtimeRegister\Api\CustomersApi;
use SandwaveIo\RealtimeRegister\Domain\PriceCollection;

class ProductsPrices
{
    /**
     *
     * @var Products
     */
    private static $instance;
    
    private $prices;
    
    /**
     * @return Products
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new ProductsPrices();
        }
        return self::$instance;
    }

    public function getAllProductsPrices() {
        $this->fetchAllProductsPrices();
        return $this->prices;
    }

    private function fetchAllProductsPrices(): array
    {
        if ($this->prices !== null) {
            return $this->prices;
        }

        /** @var CustomersApi $customersApi */
        $customersApi = ApiProvider::getInstance()->getApi(CustomersApi::class);
        /** @var PriceCollection $apiProducts */
        $apiProducts = $customersApi->priceList(ApiProvider::getCustomer());

        $this->prices = [];
        foreach ($apiProducts->toArray() as $apiProductPrice) {
            if (strpos($apiProductPrice['product'], 'ssl') !== false) {
                $pp = new ProductPrice();
                Fill::fill($pp, $apiProductPrice);
                $this->prices[] = $pp;
            }
        }
        return $this->prices;
    }
}
