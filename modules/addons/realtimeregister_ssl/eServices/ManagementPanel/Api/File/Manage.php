<?php

declare(strict_types=1);

namespace AddonModule\RealtimeRegisterSsl\eServices\ManagementPanel\Api\File;

use AddonModule\RealtimeRegisterSsl\eServices\ManagementPanel\Api\File\Exceptions\FileException;

class Manage
{
    /**
     * @var Panel
     */
    protected static $panel;
    private static $instance;

    /**
     * @param \AddonModule\RealtimeRegisterSsl\eServices\ManagementPanel\Api\Panel\Panel $panel
     * @throws FileException
     */
    public static function loadPanel($panel, array $options = [])
    {
        self::$panel = $panel;

        if (!isset(self::$instance)) {
            self::$instance = self::makeInstance(self::$panel, $options);
        }
    }


    /**
     * @param \AddonModule\RealtimeRegisterSsl\eServices\ManagementPanel\Api\Panel\Panel $panel
     * @return mixed
     * @throws FileException
     */
    public static function sendFile($panel, array $file, string $dir)
    {
        self::loadPanel($panel);

        return self::$instance->uploadFile($file, $dir);
    }

    public static function getFile($panel, $file, $dir)
    {
        self::loadPanel($panel);

        return self::$instance->getFile($file, $dir);
    }

    /**
     * @param \HostcontrolPanel\Manage $panel
     * @param $expFile
     * @return bool
     */
    public static function checkIfExists($panel, $expFile)
    {
        self::loadPanel($panel);

        $file = self::getFile($panel, $expFile, $expFile['dir']);

        if($file) {
            return false;
        }

        return $expFile['content'] == $file['content'];
    }

    /**
     * @return mixed
     * @throws FileException
     */
    private static function makeInstance(array $panel, array $options)
    {
        $api = '\AddonModule\RealtimeRegisterSsl\eServices\ManagementPanel\Api\File\Platforms\\' . ucfirst($panel['platform']);

        if (!class_exists($api)) {
            throw new FileException(sprintf("Platform `%s` not supported.", $panel['platform']), 12);
        }

        return new $api($panel + $options);
    }
}
