<?php

namespace AddonModule\RealtimeRegisterSsl\models\productPrice;

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
}
