<?php

namespace AddonModule\RealtimeRegisterSsl\models\userCommission;

use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Description of repository
 *
 */
class Repository extends \AddonModule\RealtimeRegisterSsl\addonLibs\models\Repository
{
    public $tableName = 'mod_REALTIMEREGISTERSSL_user_commission';

    public function getModelClass()
    {
        return __NAMESPACE__ . '\UserCommission';
    }
    
    /**
     *
     * @return ProductPrices[]
     */
    public function get()
    {
        return parent::get();
    }

    /**
     *
     * @return ProductPrices
     */
    public function fetchOne()
    {
        return parent::fetchOne();
    }

    public function onlyProductID($id)
    {
        $this->_filters['product_id'] = $id;

        return $this;
    }

    public function onlyClientID($id)
    {
        $this->_filters['client_id'] = $id;
        return $this;
    }

    public function onlyPeriod($period)
    {
        $this->_filters['period'] = $period;
        return $this;
    }

    public function createUserCommissionTable()
    {
        if (!Capsule::schema()->hasTable($this->tableName)) {
            Capsule::schema()->create($this->tableName, function($table) {
                $table->engine = 'InnoDB';
                $table->increments('id');
                $table->integer('client_id');
                $table->integer('product_id');
                $table->string('commission');
            });
        }
    }

    public function updateUserCommissionTable()
    {
        if (!Capsule::schema()->hasTable($this->tableName)) {
            Capsule::schema()->create($this->tableName, function($table) {
                $table->engine = 'InnoDB';
                $table->increments('id');
                $table->integer('client_id');
                $table->integer('product_id');
                $table->string('commission');
            });
        }
    }

    public function dropUserCommissionTable()
    {
        if (Capsule::schema()->hasTable($this->tableName)) {
            Capsule::schema()->dropIfExists($this->tableName);
        }
    }
}
