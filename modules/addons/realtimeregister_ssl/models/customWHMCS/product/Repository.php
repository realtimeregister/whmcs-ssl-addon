<?php

namespace AddonModule\RealtimeRegisterSsl\models\customWHMCS\product;

/**
 * Description of repository
 *
 */
class Repository extends \AddonModule\RealtimeRegisterSsl\addonLibs\models\Repository
{
    public function getModelClass()
    {
        return __NAMESPACE__.'\Product';
    }
}
