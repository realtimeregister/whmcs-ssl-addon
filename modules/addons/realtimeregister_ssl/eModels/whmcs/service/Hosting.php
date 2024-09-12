<?php

declare(strict_types=1);

namespace AddonModule\RealtimeRegisterSsl\eModels\whmcs\service;

use Illuminate\Database\Eloquent\Model;

class Hosting extends Model
{
    protected $table = 'tblhosting';
    public $timestamps = false;

    /** used to be getcPanelServices */
    public function getServicesByUserId($userid)
    {
        return static::select([
            'tblhosting.id',
            'tblhosting.domain',
            'tblhosting.username as user',
            'tblproducts.name',
            'tblservers.hostname',
            'tblservers.ipaddress',
            'tblproducts.servertype',
            'tblservers.secure',
            'tblservers.port',
            'tblservers.accesshash as hash',
            'tblservers.username',
            'tblservers.password',
            'tblhosting.packageid'
        ])
            ->join('tblproducts', 'tblhosting.packageid', '=', 'tblproducts.id')
            ->join('tblservers', 'tblhosting.server', '=', 'tblservers.id')
            ->where(function ($query) {
                $query->where('tblproducts.servertype', 'cpanel')
                    ->orWhere('tblservers.servertype', 'plesk')
                    ->orWhere('tblservers.servertype', 'directadmin');
            })
            ->where('tblhosting.userid', $userid)
            ->get();
    }

    /** was getcPanelService */
    public function getPanelByDomain(string $domain)
    {
        return static::select([
            'tblhosting.id',
            'tblhosting.domain',
            'tblhosting.username as user',
            'tblproducts.name',
            'tblservers.hostname',
            'tblservers.ipaddress',
            'tblproducts.servertype',
            'tblservers.secure',
            'tblservers.port',
            'tblservers.accesshash as hash',
            'tblservers.username',
            'tblservers.password'
        ])
            ->join('tblproducts', 'tblhosting.packageid', '=', 'tblproducts.id')
            ->join('tblservers', 'tblhosting.server', '=', 'tblservers.id')
            ->where(function ($query) {
                $query->where('tblproducts.servertype', 'cpanel')
                    ->orWhere('tblservers.servertype', 'plesk')
                    ->orWhere('tblservers.servertype', 'directadmin');
            })
            ->where('tblhosting.domain', $domain)
            ->first();
    }
}
