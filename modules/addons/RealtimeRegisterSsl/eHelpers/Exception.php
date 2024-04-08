<?php

namespace MGModule\RealtimeRegisterSsl\eHelpers;

class Exception {

    public static function e($ex) {
         
        if($_SESSION['adminid']) {
            return $ex->getMessage();
        }
        
        $class = get_class($ex);

        if ($class === 'MGModule\RealtimeRegisterSsl\mgLibs\RealtimeRegisterException') {
            return \MGModule\RealtimeRegisterSsl\mgLibs\Lang::getInstance()->T('anErrorOccurred');
        }
        
        if ($class === 'MGModule\RealtimeRegisterSsl\mgLibs\RealtimeRegisterApiException') {
            return \MGModule\RealtimeRegisterSsl\mgLibs\Lang::getInstance()->T('anErrorOccurred');
        }

        if ($class === 'Exception') {
            return $ex->getMessage();
        }
    }

}
