<?php

namespace MGModule\RealtimeRegisterSsl\models\customWHMCS\product;

/**
 * Description of repository
 *
 */
class Repository extends \MGModule\RealtimeRegisterSsl\mgLibs\models\Repository
{
    public function getModelClass()
    {
        return __NAMESPACE__.'\Product';
    }
}
