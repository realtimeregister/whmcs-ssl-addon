<?php

namespace MGModule\RealtimeRegisterSsl\models\userDiscount;

use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Description of repository
 *
 * @author Michal Czech <michael@modulesgarden.com>
 */
class Repository extends \MGModule\RealtimeRegisterSsl\mgLibs\models\Repository
{
    public $tableName = 'mgfw_REALTIMEREGISTERSSL_user_discount';

    public function getModelClass()
    {
        return __NAMESPACE__ . '\UserDiscount';
    }

    public function get()
    {
        return parent::get();
    }

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

    public function createUserDiscountTable()
    {
        if (!Capsule::schema()->hasTable($this->tableName)) {
            Capsule::schema()->create($this->tableName, function($table) {
                $table->engine = 'InnoDB';
                $table->increments('id');
                $table->integer('client_id');
                $table->integer('product_id');
                $table->integer('percentage');
            });
        }
    }

    public function updateUserDiscountTable()
    {
        if (!Capsule::schema()->hasTable($this->tableName)) {
            Capsule::schema()->create($this->tableName, function($table) {
                $table->engine = 'InnoDB';
                $table->increments('id');
                $table->integer('client_id');
                $table->integer('product_id');
                $table->integer('percentage');
            });
        }
    }

    public function dropUserDiscountTable()
    {
        if (Capsule::schema()->hasTable($this->tableName)) {
            Capsule::schema()->dropIfExists($this->tableName);
        }
    }
}