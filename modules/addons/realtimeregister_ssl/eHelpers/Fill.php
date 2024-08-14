<?php

namespace MGModule\RealtimeRegisterSsl\eHelpers;

class Fill {
    public static function fill($object, &$array) {
        foreach ($array as $key => $value) {
            $object->$key = $value;
        }
    }
}
