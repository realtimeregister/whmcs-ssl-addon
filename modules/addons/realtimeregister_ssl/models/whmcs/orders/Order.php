<?php

namespace MGModule\RealtimeRegisterSsl\models\whmcs\orders;

/**
 * Description of order
 *
 * @Table(name=tblorders,preventUpdate,prefixed=false)
 * @author Michal Czech <michael@modulesgarden.com>
 */
class Order extends \MGModule\RealtimeRegisterSsl\mgLibs\models\Orm
{
    /**
     *
     * @Column(int)
     * @var int
     */
    public $id;

    /**
     *
     * @Column()
     * @var string
     */
    public $ordernum;

    /**
     *
     * @Column()
     * @var string
     */
    public $userid;

    /**
     *
     * @Column()
     * @var string
     */
    public $date;

    /**
     *
     * @Column()
     * @var string
     */
    public $invoiceid;

    /**
     *
     * @Column()
     * @var string
     */
    public $status;

    /**
     *
     * @Column()
     * @var string
     */
    public $ipaddress;

    /**
     *
     * @var \MGModule\RealtimeRegisterSsl\models\whmcs\clients\client
     */
    private $_client;

    /**
     *
     * @var \MGModule\RealtimeRegisterSsl\models\whmcs\invoices\invoice
     */
    private $_invoice;

    /**
     *
     * @return \MGModule\RealtimeRegisterSsl\models\whmcs\clients\client
     */
    public function client()
    {
        if (empty($this->_client)) {
            $this->_client = new \MGModule\RealtimeRegisterSsl\models\whmcs\clients\client($this->userid);
        }

        return $this->_client;
    }

    /**
     *
     * @return \MGModule\RealtimeRegisterSsl\models\whmcs\invoices\invoice
     */
    public function invoice()
    {
        if (empty($this->_invoice)) {
            $this->_invoice = new \MGModule\RealtimeRegisterSsl\models\whmcs\invoices\Invoice($this->invoiceid);
        }

        return $this->_invoice;
    }

    function getOrderUrl()
    {
        return 'orders.php?action=view&id=' . $this->id;
    }
}
