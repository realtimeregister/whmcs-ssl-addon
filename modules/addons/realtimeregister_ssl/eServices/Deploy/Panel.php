<?php

declare(strict_types=1);

namespace MGModule\RealtimeRegisterSsl\eServices\Deploy;

use Illuminate\Database\Capsule\Manager as Capsule;

class Panel
{
    protected $params;

    public $instance;

    public function __construct($domain, $sid = null)
    {
        $this->params = $this->getPanelData($domain, $sid);
    }

    public static function getPanelData($domain, $sid = null)
    {
        $result = Capsule::table('tblhosting AS hosting')
            ->join('tblservers AS server', function ($join) {
                $join->on('server.id', '=', 'hosting.server');
            })
            ->when(isset($sid) || isset($domain), function ($query) use ($sid, $domain) {
                if ($sid) {
                    return $query->where('hosting.id', $sid);
                } else {
                    return $query->where('hosting.domain', $domain);
                }
            })
            ->where('hosting.server', '>', 0)
            ->first([
                'hosting.domain',
                'hosting.domainstatus',
                'server.ipaddress',
                'server.hostname',
                'server.type',
                Capsule::raw("IF(server.type='plesk', server.username, hosting.username) AS username"),
                Capsule::raw("IF(server.type='plesk', server.password, hosting.password) AS password"),
                'server.port',
                'server.secure',
            ]);

        if ($result) {
            $protocol = $result->secure == 'on' ? 'https' : 'http';

            return [
                'API_URL' => sprintf("%s://%s", $protocol, $result->hostname ?: $result->ipaddress),
                'API_USER' => $result->username,
                'API_PORT' => $result->port,
                'API_PASSWORD' => \decrypt($result->password, $GLOBALS['cc_encryption_hash']),
                'platform' => $result->type,
                'domain' => $result->domain,
                'ip' => $result->ipaddress,
                'status' => $result->domainstatus,
                'allowance' => self::getPanelsFunction($result->type),
            ];
        }

        return false;
    }

    public static function getPanelsFunction(string $platform): array
    {
        switch ($platform) {
            case "cpanel":
            case "directadmin-extended":
            case "directadmin":
                $allowance = [
                    'dns' => true,
                    'file' => true,
                    'deploy' => true,
                    'nokey' => true,
                ];
                break;
            case "plesk":
                $allowance = [
                    'dns' => true,
                    'file' => false,
                    'deploy' => true,
                    'nokey' => false,
                ];
                break;
            case "hostcontrol":
                $allowance = [
                    'dns' => true,
                    'file' => false,
                    'deploy' => false,
                    'nokey' => false,
                ];
                break;
            default:
                $allowance = false;
                break;
        }

        return $allowance;
    }
}
