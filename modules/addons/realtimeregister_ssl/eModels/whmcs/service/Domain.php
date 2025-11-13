<?php

declare(strict_types=1);

namespace AddonModule\RealtimeRegisterSsl\eModels\whmcs\service;

use Illuminate\Database\Eloquent\Model;

class Domain extends Model
{
    public const TABLE_NAME = 'tbldomains';
    protected $table = self::TABLE_NAME;
}
