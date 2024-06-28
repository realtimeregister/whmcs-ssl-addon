<?php

declare(strict_types=1);

namespace MGModule\RealtimeRegisterSsl\eModels\whmcs\service;

use Illuminate\Database\Eloquent\Model;
use MGModule\RealtimeRegisterSsl\eServices\Deploy\Panel\Manage;

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

    // FIXME
    public function getDomainByUser(int $userid)
    {
        $domains = [];
        $services = self::getServicesByUserId($userid);

        foreach ($services as $service) {
            try {
                $cpanel = new Cpanel();
                $cpanel->setService($service);
                $domains = array_merge($domains, $cpanel->listDomains($service->user));
            } catch (\Exception $e) {
                \logActivity($e->getMessage(), 0);
            }
        }

        return $domains;
    }

    // FIXME
    public function getServiceByDomain(int $userid, string $domain)
    {
        $services = self::getServicesByUserId($userid);
        foreach ($services as $service) {
            try {
                $panel = new Manage($service->domain);

                $panelData = $panel->getPanelData();

                $cpanel = new Cpanel();
                $cpanel->setService($service);
                foreach ($cpanel->listDomains($service->user) as $cpaneldomain) {
                    if ($domain == $cpaneldomain) {
                        return $service;
                    }
                }
            } catch (\Exception $e) {
                \logActivity($e->getMessage(), 0);
            }
        }
        return false;
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