<?php

declare(strict_types=1);

namespace MGModule\RealtimeRegisterSsl\eServices\Deploy\API\Ssl;

interface PlatformInterface
{
    /**
     * @return array [csr, key, keyId]
     * @throws \Exception
     */
    public function genKeyCsr(string $domain, array $csrData): array;

    /**
     * @param string $crt
     * @return string
     * @throws \Exception
     */
    public function uploadCertificate(string $domain, array $crt);

    /**
     * @throws \Exception
     */
    public function installCertificate(
        string $domain, ?string $key, string $crt, string $csr = null, string $ca = null
    ): string;

    /**
     * @param string $id
     * @return string
     * @throws \Exception
     */
    public function getKey(string $domain, $id);
}
