<?php

declare(strict_types=1);

namespace MGModule\RealtimeRegisterSsl\eServices\Deploy\API\File;

interface PlatformInterface
{
    /**
     * @param $file
     * @param string $dir
     * @return mixed
     * @throws \Exception
     */
    public function uploadFile($file, $dir);

    /**
     * @param $file
     * @param string $dir
     * @return mixed
     * @throws \Exception
     */
    public function getFile($file, $dir);
}
