<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AddonModule\RealtimeRegisterSsl\eHelpers;
use Whmcs\Database\Capsule;

/**
 * Description of Invoice
 *
 */
class Admin
{
    protected static $adminUserName = null;
    
    public static function getAdminUserName() {
        if (!self::$adminUserName) {
            self::$adminUserName = Capsule::table('tbladmins')
                ->limit(1)
                ->first()
                ->username;
        }
        return self::$adminUserName;
    }
}
