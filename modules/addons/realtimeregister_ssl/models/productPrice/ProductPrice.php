<?php

namespace AddonModule\RealtimeRegisterSsl\models\productPrice;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int id
 * @property int api_product_id
 * @property string|float price
 * @property string period
 * @property string action
 * @property string currency
 */
class ProductPrice extends Model
{

    public const TABLE_NAME = 'REALTIMEREGISTERSSL_api_product_prices';
    protected $table = self::TABLE_NAME;
    public $timestamps = false;
    
    public function getID()
    {
        return $this->id;
    }

    public function getApiProductID()
    {
        return $this->api_product_id;
    }

    public function setApiProductID($id)
    {
        $this->api_product_id = $id;
    }

    public function getPrice()
    {
        return $this->price;
    }

    public function setPrice($price)
    {
        $this->price = $price;
    }

    public function getPeriod()
    {
        return $this->period;
    }

    public function setPeriod($period)
    {
        $this->period = $period;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function setAction(string $action): void
    {
        $this->action = $action;
    }


    public function getCurrency(): string
    {
        return $this->action;
    }

    public function setCurrency(string $currency): void
    {
        $this->currency = $currency;
    }

    public static function createApiProductsPricesTable()
    {
        if (!Capsule::schema()->hasTable(self::TABLE_NAME)) {
            Capsule::schema()->create(self::TABLE_NAME, function ($table) {
                $table->increments('id');
                $table->integer('api_product_id');
                $table->string('price');
                $table->string('period');
                $table->string("action");
                $table->string("currency");
            });
        }
    }

    public static function dropApiProductsPricesTable()
    {
        if (Capsule::schema()->hasTable(self::TABLE_NAME)) {
            Capsule::schema()->dropIfExists(self::TABLE_NAME);
        }
    }
}
