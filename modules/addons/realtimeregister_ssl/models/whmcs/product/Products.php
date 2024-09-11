<?php

namespace AddonModule\RealtimeRegisterSsl\models\whmcs\product;

/**
 * Description of repository
 *
 */
class Products extends \AddonModule\RealtimeRegisterSsl\mgLibs\models\Repository
{
    public function getModelClass()
    {
        return __NAMESPACE__ . '\Product';
    }

    public function get()
    {
        return parent::get();
    }

    public function onlyModule($module)
    {
        $this->_filters['servertype'] = $module;
        return $this;
    }
}
