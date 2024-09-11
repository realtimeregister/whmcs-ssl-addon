<?php

namespace AddonModule\RealtimeRegisterSsl\eRepository\whmcs\service;

use AddonModule\RealtimeRegisterSsl\eModels\whmcs\service\SSL as Model;
use Exception;

class SSL
{
    /**
     * @param int $id
     * @return \AddonModule\RealtimeRegisterSsl\eModels\whmcs\service\SSL
     */
    public function getSingle($id)
    {
        $model = Model::find($id);
        if (is_null($model)) {
            throw new Exception('Invalid SSL Order');
        }
        return $model;
    }

    /**
     * @param int $id
     * @return \AddonModule\RealtimeRegisterSsl\eModels\whmcs\service\SSL
     */
    public function getByServiceId($id)
    {
        return Model::whereServiceId($id)->first();
    }
    /**
     * @param int $id
     * @return \AddonModule\RealtimeRegisterSsl\eModels\whmcs\service\SSL
     */
    public function getByRemoteId($id)
    {
        return Model::whereRemoteId($id)->first();
    }

    /**
     * @param string $status
     * @return \AddonModule\RealtimeRegisterSsl\eModels\whmcs\service\SSL
     */
    public function getBy($where, $realtimeregisterssl = false)
    {
        return Model::getWhere($where, $realtimeregisterssl)->get();
    }
}
