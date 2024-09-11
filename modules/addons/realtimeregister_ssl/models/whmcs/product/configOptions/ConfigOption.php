<?php

namespace MGModule\RealtimeRegisterSsl\models\whmcs\product\configOptions;

/**
 * Config Options Model
 *
 * @Table(name=tblproductconfigoptions,preventUpdate,prefixed=false)
 */
class ConfigOption extends \MGModule\RealtimeRegisterSsl\mgLibs\models\Orm
{
    /**
     *
     * @Column()
     * @var int
     */
    public $id;

    /**
     *
     * @Column()
     * @var int
     */
    public $gid;

    /**
     *
     * @Column(name=optionname,as=optionname)
     * @var string
     */
    public $name;

    /**
     *
     * @var string
     */
    public $frendlyName;

    /**
     *
     * @Column(name=optiontype,as=optiontype)
     * @var string
     */
    public $type;

    /**
     *
     * @Column(name=qtyminimum)
     * @var int
     */
    public $qtymin;

    /**
     *
     * @Column(name=qtymaximum)
     * @var int
     */
    public $qtymax;

    /**
     *
     * @Column()
     * @var int
     */
    public $order;

    /**
     *
     * @Column()
     * @var boolean
     */
    public $hidden;
}
