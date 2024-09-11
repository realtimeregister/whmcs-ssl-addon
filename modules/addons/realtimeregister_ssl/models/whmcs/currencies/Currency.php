<?php

/* * ********************************************************************
 *
 *
 * This software is furnished under a license and may be used and copied
 * only  in  accordance  with  the  terms  of such  license and with the
 * inclusion of the above copyright notice.  This software  or any other
 * copies thereof may not be provided or otherwise made available to any
 * other person.  No title to and  ownership of the  software is  hereby
 * transferred.
 *
 *
 * ******************************************************************** */

namespace AddonModule\RealtimeRegisterSsl\models\whmcs\currencies;

use AddonModule\RealtimeRegisterSsl\addonLibs\models\Orm;
use AddonModule\RealtimeRegisterSsl\addonLibs\MySQL\Query;

/**
 * Description of Currency
 *
 * @Table(name=tblcurrencies,preventUpdate,prefixed=false)
 */
class Currency extends Orm
{
    /**
     * @Column()
     * @var int
     */
    protected $id;

    /**
     * @Column(name=code)
     * @var string
     */
    protected $code;

    /**
     * @Column(name=prefix)
     * @var string
     */
    protected $prefix;

    /**
     * @Column(name=suffix)
     * @var string
     */
    protected $suffix;

    /**
     * @Column(name=format)
     * @var int
     */
    protected $format;

    /**
     * @Column(name=rate)
     * @var int
     */
    protected $rate;

    /**
     * @Column(name=default)
     * @var int
     */
    protected $default;

    public function __construct($id = false, $data = [])
    {
        if ($id === "0") {
            $row = Query::select(["id"], self::tableName(), ["default" => "1"])->fetch();
            $id = $row['id'];
        }
        parent::__construct($id, $data);
    }

    public function getId()
    {
        return $this->id;
    }

    public function getCode()
    {
        return $this->code;
    }

    public function getPrefix()
    {
        return $this->prefix;
    }

    public function getSuffix()
    {
        return $this->suffix;
    }

    public function getFormat()
    {
        return $this->format;
    }

    public function getRate()
    {
        return $this->rate;
    }

    public function getDefault()
    {
        return $this->default;
    }
}
