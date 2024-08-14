<?php

namespace MGModule\RealtimeRegisterSsl\models\productPrice;

/**
 * @Table(name=REALTIMEREGISTERSSL_api_product_prices)
 */
class ProductPrice extends \MGModule\RealtimeRegisterSsl\mgLibs\models\Orm
{
    /**
     * 
     * @Column(id)
     * @var integer
     */
    public $id;

    /**
     * 
     * @Column(api_product_id)
     * @var int
     */
    public $api_product_id;

    /**
     * @Column(varchar=32)
     * @var string|float
     */
    public $price;

    /**
     * @Column(varchar=32)
     * @var string
     */
    public $period;

    /**
     * @Column(varchar=32)
     * @var string
     */
    public $action;

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
}
