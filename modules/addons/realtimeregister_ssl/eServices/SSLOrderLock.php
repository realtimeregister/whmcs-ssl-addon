<?php

namespace AddonModule\RealtimeRegisterSsl\eServices;

use Illuminate\Database\Capsule\Manager as Capsule;
use RuntimeException;

/**
 * Lock on a single SSL order (tblsslorders.id), based on MySQL named locks.
 *
 * The lock belongs to the database connection, so it is released automatically when the process ends.
 */
class SSLOrderLock
{
    /**
     * Run the callback while holding the lock of the given SSL order
     *
     * @param int $waitSeconds how long to wait for the lock, 0 to give up immediately
     * @return bool false if the order is locked by another process, in which case the callback isn't called
     */
    public static function run(int $sslOrderId, callable $callback, int $waitSeconds = 0): bool
    {
        $name = self::getName($sslOrderId);

        if (!self::acquire($name, $waitSeconds)) {
            return false;
        }

        try {
            $callback();
        } finally {
            Capsule::connection()->selectOne('SELECT RELEASE_LOCK(?) AS released', [$name], false);
        }

        return true;
    }

    private static function acquire(string $name, int $waitSeconds): bool
    {
        $result = Capsule::connection()->selectOne(
            'SELECT GET_LOCK(?, ?) AS acquired',
            [$name, $waitSeconds],
            false
        );

        // 1 = acquired, 0 = timed out, NULL = error
        if (!isset($result->acquired)) {
            throw new RuntimeException('Unable to request lock ' . $name);
        }

        return (int) $result->acquired === 1;
    }

    /**
     * Named locks are shared by the whole MySQL server and limited to 64 characters, so include the (hashed) database
     * name to keep multiple installations on the same server apart.
     */
    private static function getName(int $sslOrderId): string
    {
        return 'rtrssl_' . md5(Capsule::connection()->getDatabaseName() . ':' . $sslOrderId);
    }
}
