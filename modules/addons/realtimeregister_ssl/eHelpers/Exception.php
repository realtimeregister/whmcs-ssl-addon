<?php

namespace AddonModule\RealtimeRegisterSsl\eHelpers;

class Exception {
    public static function e($ex) {
        if($_SESSION['adminid']) {
            return $ex->getMessage();
        }
        
        $class = get_class($ex);

        if ($class === 'AddonModule\RealtimeRegisterSsl\mgLibs\RealtimeRegisterException') {
            return \AddonModule\RealtimeRegisterSsl\mgLibs\Lang::getInstance()->T('anErrorOccurred');
        }
        
        if ($class === 'AddonModule\RealtimeRegisterSsl\mgLibs\RealtimeRegisterApiException') {
            return \AddonModule\RealtimeRegisterSsl\mgLibs\Lang::getInstance()->T('anErrorOccurred');
        }

        if ($class === 'Exception') {
            return $ex->getMessage();
        }
    }
}
