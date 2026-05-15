<?php

namespace AddonModule\RealtimeRegisterSsl\eHelpers;

use AddonModule\RealtimeRegisterSsl\models\apiConfiguration\Repository as ConfigRepo;
use WHMCS\View\Formatter\Price;

class Whmcs
{
    public static function savelogActivityRealtimeRegisterSsl($msg)
    {
        $apiConf = (new ConfigRepo())->get();
        if ($apiConf->save_activity_logs) {
            logActivity($msg);
        }
    }
}
