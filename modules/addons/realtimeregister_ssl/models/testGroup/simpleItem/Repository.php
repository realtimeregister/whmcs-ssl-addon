<?php

namespace MGModule\RealtimeRegisterSsl\models\testGroup\simpleItem;

/**
 * Description of repository
 *
 */
class Repository extends \MGModule\RealtimeRegisterSsl\mgLibs\models\Repository
{
    public function getModelClass()
    {
        return __NAMESPACE__.'\SimpleItem';
    }
}
