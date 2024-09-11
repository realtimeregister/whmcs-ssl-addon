<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AddonModule\RealtimeRegisterSsl\eHelpers;

/**
 * Description of Invoice
 *
 */
class Admin
{
    protected static $adminUserName = null;
    
    public static function getAdminUserName() {
        if (!self::$adminUserName) {
            self::$adminUserName = \AddonModule\RealtimeRegisterSsl\addonLibs\MySQL\Query::query(
                'SELECT username FROM tbladmins LIMIT 1'
            )->fetchColumn('username');
        }
        return self::$adminUserName;
    }
}
