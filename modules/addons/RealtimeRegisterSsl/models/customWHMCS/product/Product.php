<?php

namespace MGModule\RealtimeRegisterSsl\models\customWHMCS\product;
use MGModule\RealtimeRegisterSsl as main;

/**
 * @SuppressWarnings(PHPMD)
 */
class Product extends MGModule\RealtimeRegisterSsl\models\whmcs\product\product{
    function loadConfiguration($params){
        return new Configuration($this->id);
    }
}
