<?php

namespace MGModule\RealtimeRegisterSsl\eServices\Deploy\API\Platforms;

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
    public function uploadCertificate(string $domain, $crt);

    /**
     * @param string $key
     * @param string $csr
     * @param string $ca
     * @return string
     * @throws \Exception
     */
    public function installCertificate(string $domain, $key, array $crt, $csr = null, $ca = null);

    /**
     * @param string $id
     * @return string
     * @throws \Exception
     */
    public function getKey(string $domain, $id);
}
