<?php

namespace MGModule\RealtimeRegisterSsl\models\customWHMCS\product;

/**
 * @SuppressWarnings(PHPMD)
 */
class Product extends MGModule\RealtimeRegisterSsl\models\whmcs\product\product
{
    function loadConfiguration($params)
    {
        return new Configuration($this->id);
    }
}
