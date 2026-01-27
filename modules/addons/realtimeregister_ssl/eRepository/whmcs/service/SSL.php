<?php

namespace AddonModule\RealtimeRegisterSsl\eRepository\whmcs\service;

use AddonModule\RealtimeRegisterSsl\eModels\whmcs\service\SSL as Model;
use Exception;

class SSL
{
    /**
     * @param int $id
     * @return Model
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
     * @return Model
     */
    public function getByServiceId($id)
    {
        return Model::whereServiceId($id)->first();
    }
    /**
     * @param int $id
     */
    public function getByRemoteId($id)
    {
        return Model::query()->where('remoteid', '=', $id)->first();
    }

    /**
     * @param string $status
     * @return Model
     */
    public function getBy($where, $realtimeregisterssl = false)
    {
        return Model::getWhere($where, $realtimeregisterssl)->get();
    }

    public function getOrdersWithStatus($status) {
        return Model::query()
            ->select(['tblsslorders.*'])
            ->join('tblhosting', 'tblhosting.id', '=', 'serviceid')
            ->where('tblhosting.domainstatus', 'Active')
            ->whereIn('status', $status)
            ->get();
    }
}
