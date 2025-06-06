<?php

namespace AddonModule\RealtimeRegisterSsl\models\orders;

use AddonModule\RealtimeRegisterSsl\addonLibs\models\Repository as MainRepository;
use AddonModule\RealtimeRegisterSsl\eModels\whmcs\service\SSL;
use Illuminate\Database\Capsule\Manager as Capsule;

class Repository extends MainRepository
{
    public $tableName = 'REALTIMEREGISTERSSL_orders';

    public function getModelClass()
    {
        return __NAMESPACE__ . '\Order';
    }

    public function get()
    {
        return Capsule::table($this->tableName)->get();
    }

    public function getByServiceId($serviceId)
    {
        return Capsule::table($this->tableName)
            ->where('service_id', $serviceId)
            ->first();
    }

    public function remove($id)
    {
        Capsule::table($this->tableName)->where('id', $id)->delete();
    }

    public function getOrdersInstallation()
    {
        return Capsule::table($this->tableName)
            ->select([$this->tableName.'.*', 'tblsslorders.configdata', 'tblhosting.domain'])
            ->join('tblhosting', 'tblhosting.id', '=', $this->tableName.'.service_id')
            ->join('tblsslorders', 'tblsslorders.serviceid', '=', $this->tableName.'.service_id')
            ->where([
                [$this->tableName.'.status', 'NOT LIKE', '%"ssl_status":"COMPLETED"%'],
                [$this->tableName.'.status', 'NOT LIKE', '%"ssl_status":"INVALID"%'],
                [$this->tableName.'.status', 'NOT LIKE', '%"ssl_status":"REJECTED"%'],
                [$this->tableName.'.status', '!=', 'Success']
            ])
            ->orWhere('tblsslorders.status', SSL::CONFIGURATION_SUBMITTED)
            ->get();
    }

    public function checkOrdersInstallation($serviceId)
    {
        $checkOrder = Capsule::table($this->tableName)
            ->where('service_id', $serviceId)
            ->first();

        if (!isset($checkOrder->id)) {
            return true;
        }

        if (isset($checkOrder->status) && $checkOrder->status == SSL::PENDING_INSTALLATION) {
            return true;
        }

        return false;
    }

    public function getList($limit, $offset, $orderBy = [], $search = '')
    {
        if (empty($search)) {
            $query = Capsule::table($this->tableName)
                ->limit($limit)
                ->offset($offset)
                ->orderBy($orderBy[0], $orderBy[1]);

            return [
                'results' => $query->get(),
                'count' => self::count()
            ];
        }

        $query = Capsule::table($this->tableName)
            ->select([$this->tableName.'.*'])
            ->join('tblclients', 'tblclients.id', '=', $this->tableName.'.client_id')
            ->join('tblhosting', 'tblhosting.id', '=', $this->tableName.'.service_id')
            ->join('tblproducts', 'tblproducts.id', '=', 'tblhosting.packageid')
            ->join('tblsslorders', 'tblsslorders.serviceid', '=', 'tblhosting.id')
            ->where(
                Capsule::raw("CONCAT(tblclients.firstname,' ',tblclients.lastname,' ',tblclients.companyname)"),
                'like',
                '%'.$search.'%'
            )
            ->orWhere(Capsule::raw("CONCAT(tblhosting.domain,' - ',tblproducts.name)"), 'like', '%'.$search.'%')
            ->orWhere(Capsule::raw("CONCAT(tblsslorders.remoteid)"), 'like', '%'.$search.'%')
            ->orWhere($this->tableName.'.verification_method', 'like', '%'.$search.'%')
            ->orWhere($this->tableName.'.status', 'like', '%'.$search.'%')
            ->orWhere($this->tableName.'.date', 'like', '%'.$search.'%')
            ->limit($limit)
            ->offset($offset)
            ->orderBy($orderBy[0], $orderBy[1]);

        return [
            'results' => $query->get(),
            'count' => $query->count()
        ];
    }

    public function addOrder($clientId, $serviceId, $sslOrderId, $verificationMethod, $status, $data)
    {
        $currentOrder = Capsule::table($this->tableName)
            ->where('service_id', '=', $serviceId)
            ->first();
        if ($currentOrder) {
            Capsule::table($this->tableName)
                ->where('id', '=', $currentOrder->id)
                ->update([
                    'ssl_order_id' => $sslOrderId,
                    'verification_method' => $verificationMethod,
                    'status' => $status,
                    'data' => json_encode($data),
                    'date' => date('Y-m-d H:i:s')
                ]);

            return;
        }
        Capsule::table($this->tableName)->insert([
            'client_id' => $clientId,
            'service_id' => $serviceId,
            'ssl_order_id' => $sslOrderId,
            'verification_method' => $verificationMethod,
            'status' => $status,
            'data' => json_encode($data),
            'date' => date('Y-m-d H:i:s')
        ]);
    }

    public function updateStatus($serviceid, $status)
    {
        Capsule::table($this->tableName)->where('service_id', $serviceid)->update([
            'status' => $status
        ]);
        if ($status == 'Success') {
            Capsule::table('tblsslorders')->where('serviceid', $serviceid)->update([
                'status' => SSL::ACTIVE
            ]);
        }
    }

    public function updateStatusById($id, $status)
    {
        Capsule::table($this->tableName)->where('id', $id)->update([
            'status' => $status
        ]);
    }

    public function createOrdersTable()
    {
        if (!Capsule::schema()->hasTable($this->tableName)) {
            Capsule::schema()->create($this->tableName, function($table) {
                $table->engine = 'InnoDB';
                $table->increments('id');
                $table->integer('client_id');
                $table->integer('service_id');
                $table->integer('ssl_order_id');
                $table->string('verification_method');
                $table->string('status');
                $table->text('data');
                $table->datetime('date');
            });
        }
        $this->removeJsonFieldFromTable();
    }

    public function updateOrdersTable()
    {
        if (!Capsule::schema()->hasTable($this->tableName)) {
            Capsule::schema()->create($this->tableName, function($table) {
                $table->engine = 'InnoDB';
                $table->increments('id');
                $table->integer('client_id');
                $table->integer('service_id');
                $table->integer('ssl_order_id');
                $table->string('verification_method');
                $table->string('status');
                $table->text('data');
                $table->datetime('date');
            });
        }
        $this->removeJsonFieldFromTable();
    }

    public function dropOrdersTable()
    {
        if (Capsule::schema()->hasTable($this->tableName)) {
            Capsule::schema()->dropIfExists($this->tableName);
        }
    }

    private function removeJsonFieldFromTable()
    {
        if (
            Capsule::schema()->hasTable($this->tableName) && Capsule::schema()->hasColumn($this->tableName, 'data')
        ) {
            // Convert JSON fieldtype to TEXT, because of missing MariaDB support
            $connection = Capsule::connection()->getPdo();
            $statement = $connection->prepare('ALTER TABLE ' . $this->tableName . ' MODIFY data TEXT NOT NULL');
            $statement->execute();
        }
    }
}
