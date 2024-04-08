<?php

namespace MGModule\RealtimeRegisterSsl\models\testGroup\simpleItem;
use MGModule\RealtimeRegisterSsl as main;

/**
 * Description of repository
 *
 * @author Michal Czech <michael@modulesgarden.com>
 */
class Repository extends \MGModule\RealtimeRegisterSsl\mgLibs\models\Repository{
    public function getModelClass() {
        return __NAMESPACE__.'\SimpleItem';
    }
}
