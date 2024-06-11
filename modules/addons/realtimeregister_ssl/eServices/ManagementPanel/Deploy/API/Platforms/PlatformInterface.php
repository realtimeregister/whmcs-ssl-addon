<?php

declare(strict_types=1);

namespace MGModule\RealtimeRegisterSsl\eServices\ManagementPanel\Deploy\Api\Platforms;

use Exception;

interface PlatformInterface
{
    /**
     * @return array [csr, key, keyId]
     * @throws Exception
     */
    public function genKeyCsr(string $domain, array $csrData): array;

    /**
     * @throws Exception
     */
    public function uploadCertificate(string $domain, string $crt);

    /**
     * @throws Exception
     */
    public function installCertificate(string $domain, string $key, string $crt, string $csr = null, string $ca = null): string;

    /**
     * @throws Exception
     */
    public function getKey(string $domain, string $id): string;
}
