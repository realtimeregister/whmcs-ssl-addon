<?php

namespace MGModule\RealtimeRegisterSsl\mgLibs\whmcsAPI;

use MGModule\RealtimeRegisterSsl\mgLibs\MySQL\Query;

class WhmcsAPI
{
    static function getAdmin()
    {
          static $username;
          
          if (empty($username)) {
                $data = Query::select(['username'], 'tbladmins', [], [], 1)->fetch();
                $username = $data['username'];
          }
          
          return $username;
    }

    static function request($command,$config)
    {
        $result = localAPI($command,$config,self::getAdmin());
        
        if ($result['result'] == 'error') {
            throw new \MGModule\RealtimeRegisterSsl\mgLibs\exceptions\WhmcsAPI($result['message']);
        }
        
        return $result;
    }
    
    static function getAdminDetails($adminId)
    {
        $data = Query::select(['username'], 'tbladmins', ["id" =>$adminId], [], 1)->fetch();
        $username = $data['username'];
        
        $result = localAPI("getadmindetails", [],$username);
        if ($result['result'] == 'error') {
            throw new \MGModule\RealtimeRegisterSsl\mgLibs\exceptions\WhmcsAPI($result['message']);
        }
            
        $result['allowedpermissions'] = explode(",", $result['allowedpermissions']);
        return  $result;
    }
}
