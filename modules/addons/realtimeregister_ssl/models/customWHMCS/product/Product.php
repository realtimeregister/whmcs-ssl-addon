<?php

namespace AddonModule\RealtimeRegisterSsl\models\customWHMCS\product;

/**
 * @SuppressWarnings(PHPMD)
 */
class Product extends \AddonModule\RealtimeRegisterSsl\models\whmcs\product\Product
{
    function loadConfiguration($params  = [])
    {
        return new Configuration($this->id);
    }
}
