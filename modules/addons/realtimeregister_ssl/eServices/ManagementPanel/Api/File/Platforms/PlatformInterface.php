<?php

declare(strict_types=1);

namespace MGModule\RealtimeRegisterSsl\eServices\ManagementPanel\Api\File\Platforms;

use Exception;

interface PlatformInterface
{
    /**
     * @return mixed
     * @throws Exception
     */
    public function uploadFile(array $file, string $dir);

    /**
     * @return mixed
     * @throws Exception
     */
    public function getFile(string $file, string $dir);
}
