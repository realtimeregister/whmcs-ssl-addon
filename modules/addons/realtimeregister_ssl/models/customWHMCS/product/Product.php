<?php

namespace MGModule\RealtimeRegisterSsl\models\customWHMCS\product;

/**
 * @SuppressWarnings(PHPMD)
 */
class Product extends \MGModule\RealtimeRegisterSsl\models\whmcs\product\Product
{
    function loadConfiguration($params  = [])
    {
        return new Configuration($this->id);
    }
}
