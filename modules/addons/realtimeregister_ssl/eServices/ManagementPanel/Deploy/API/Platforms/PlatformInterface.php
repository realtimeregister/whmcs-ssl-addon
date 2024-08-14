<?php

declare(strict_types=1);

namespace MGModule\RealtimeRegisterSsl\eServices\ManagementPanel\Deploy\Api\Platforms;

use Exception;

interface PlatformInterface
{

    /**
     * @throws Exception
     */
    public function uploadCertificate(string $domain, string $crt);

    /**
     * @throws Exception
     */
    public function installCertificate(string $domain, string $key, string $crt, string $csr = null, string $ca = null): string;
}
