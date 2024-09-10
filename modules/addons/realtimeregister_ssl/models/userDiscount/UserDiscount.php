<?php
namespace MGModule\RealtimeRegisterSsl\models\userDiscount;

/**
 * @Table(name=REALTIMEREGISTERSSL_user_discount,prefixed=true)
 */
class UserDiscount extends \MGModule\RealtimeRegisterSsl\mgLibs\models\Orm
{
    /**
     *
     * @Column(id)
     * @var type
     */
    public $id;

    /**
     *
     * @Column(product_id)
     * @var type
     */
    public $product_id;

    /**
     *
     * @Column(client_id)
     * @var type
     */
    public $client_id;

    /**
     *
     * @Column(percentage)
     * @var type
     */
    public $percentage;

    public function getID()
    {
        return $this->id;
    }

    public function getProductID()
    {
        return $this->product_id;
    }

    public function setProductID($id)
    {
        $this->product_id = $id;
    }

    public function getClientID()
    {
        return $this->client_id;
    }

    public function setClientID($id)
    {
        $this->client_id = $id;
    }

    public function getPercentage()
    {
        return $this->percentage;
    }

    public function setPercentage($value)
    {
        $this->percentage = $value;
    }
}