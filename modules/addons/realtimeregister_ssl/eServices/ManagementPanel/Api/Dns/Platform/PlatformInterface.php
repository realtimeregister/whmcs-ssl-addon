<?php

declare(strict_types=1);

namespace AddonModule\RealtimeRegisterSsl\eServices\ManagementPanel\Api\Dns\Platform;

use Exception;

interface PlatformInterface
{
    public function createDNSRecord(string $domain, string $name, string $value, string $type);

    /**
     * @return mixed
     * @throws Exception
     */
    public function getDNSRecord(string $domain);
}
