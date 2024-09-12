<?php

namespace AddonModule\RealtimeRegisterSsl\models\productPrice;

use Illuminate\Database\Capsule\Manager as Capsule;

class Repository extends \AddonModule\RealtimeRegisterSsl\addonLibs\models\Repository
{
    public $tableName = 'REALTIMEREGISTERSSL_api_product_prices';

    public function getModelClass()
    {
        return __NAMESPACE__ . '\ProductPrice';
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

    public function onlyApiProductID($id)
    {
        $this->_filters['api_product_id'] = $id;
        return $this;
    }
    
    public function onlyPeriod($period)
    {
        $this->_filters['period'] = $period;
        return $this;
    }

    public function onlyAction($action)
    {
        $this->_filters['action'] = $action;
        return $this;
    }
    
    public function createApiProductsPricesTable()
    {
        if (!Capsule::schema()->hasTable($this->tableName)) {
            Capsule::schema()->create($this->tableName, function($table) {
                $table->increments('id');
                $table->integer('api_product_id');
                $table->string('price');
                $table->string('period');
                $table->string("action");
                $table->string("currency");
            });
        }
    }

    public function updateApiProductsPricesTable()
    {
        if (!Capsule::schema()->hasTable($this->tableName)) {
            Capsule::schema()->create($this->tableName, function($table) {
                $table->increments('id');
                $table->integer('api_product_id');
                $table->string('price');
                $table->string('period');
                $table->string("action");
                $table->string("currency");
            });
        }
    }

    public function dropApiProductsPricesTable()
    {
        if (Capsule::schema()->hasTable($this->tableName)) {
            Capsule::schema()->dropIfExists($this->tableName);
        }
    }
}
