<?php

namespace AddonModule\RealtimeRegisterSsl\eProviders;

use Exception;

class PathProvider
{
    public static function getWhmcsPath()
    {
        $currentDir = __DIR__;
        for ($i = 1; $i < 5; $i++) {
            $currentDir = dirname($currentDir);
        }
        return $currentDir;
    }

    public static function getPath($path = [])
    {
        return implode(DIRECTORY_SEPARATOR, array_merge([self::getWhmcsPath()], $path));
    }

    public static function getWhmcsCountriesPatch()
    {
        return self::getPath(['resources', 'country', 'dist.countries.json']);
    }
}
