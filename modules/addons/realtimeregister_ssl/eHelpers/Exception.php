<?php

namespace AddonModule\RealtimeRegisterSsl\eHelpers;

class Exception {
    public static function e($ex) {
        if($_SESSION['adminid']) {
            return $ex->getMessage();
        }
        
        $class = get_class($ex);

        if ($class === 'AddonModule\RealtimeRegisterSsl\addonLibs\RealtimeRegisterException') {
            return \AddonModule\RealtimeRegisterSsl\addonLibs\Lang::getInstance()->T('anErrorOccurred');
        }
        
        if ($class === 'AddonModule\RealtimeRegisterSsl\addonLibs\RealtimeRegisterApiException') {
            return \AddonModule\RealtimeRegisterSsl\addonLibs\Lang::getInstance()->T('anErrorOccurred');
        }

        if ($class === 'Exception') {
            return $ex->getMessage();
        }
    }
}
