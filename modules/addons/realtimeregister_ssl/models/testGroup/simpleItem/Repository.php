<?php

namespace AddonModule\RealtimeRegisterSsl\models\testGroup\simpleItem;

/**
 * Description of repository
 *
 */
class Repository extends \AddonModule\RealtimeRegisterSsl\mgLibs\models\Repository
{
    public function getModelClass()
    {
        return __NAMESPACE__.'\SimpleItem';
    }
}
