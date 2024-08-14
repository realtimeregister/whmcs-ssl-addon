<?php

declare(strict_types=1);

namespace MGModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl;

use Illuminate\Database\Capsule\Manager as Capsule;

class KeyToIdMapping
{
    const REALTIMEREGISTERSSL_KEY_ID_MAPPING = 'mgfw_REALTIMEREGISTERSSL_key_id_mapping';

    public function createTable(): void
    {
        Capsule::schema()->create(self::REALTIMEREGISTERSSL_KEY_ID_MAPPING, function ($table) {
            $table->increments('id');
            $table->string('identifier');
        });
    }

    public static function getIdByKey(string $key): int
    {
        $result = Capsule::table(self::REALTIMEREGISTERSSL_KEY_ID_MAPPING)->where(['identifier' => $key])->first();

        if (!$result) {
            return self::add($key);
        }
        return $result->id;
    }

    public static function add(string $key)
    {
        return Capsule::table(self::REALTIMEREGISTERSSL_KEY_ID_MAPPING)->insertGetId(['identifier' => $key]);
    }

    public function dropTable(): void
    {
        if (Capsule::schema()->hasTable(self::REALTIMEREGISTERSSL_KEY_ID_MAPPING)) {
            Capsule::schema()->drop(self::REALTIMEREGISTERSSL_KEY_ID_MAPPING);
        }
    }
}
