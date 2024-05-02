<?php

declare(strict_types=1);

namespace MGModule\RealtimeRegisterSsl\eServices\Deploy\API\Dns;

interface PlatformInterface
{
    public function createDNSRecord(string $domain, string $name, string $value, string $type): string;

    /**
     * @return mixed
     * @throws \Exception
     */
    public function getDNSRecord(string $domain): array;
}
