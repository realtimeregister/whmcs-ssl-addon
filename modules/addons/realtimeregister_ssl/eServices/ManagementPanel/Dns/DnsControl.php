<?php

namespace AddonModule\RealtimeRegisterSsl\eServices\ManagementPanel\Dns;

use Exception;
use AddonModule\RealtimeRegisterSsl\eServices\ManagementPanel\Api\Dns\Manage;
use AddonModule\RealtimeRegisterSsl\eServices\ManagementPanel\Api\Dns\Manage as DNSManage;
use AddonModule\RealtimeRegisterSsl\eServices\ManagementPanel\Validation\Manage as ValidationManage;

class DnsControl
{
    /**
     * @param Certificate $certificate
     * @param Manage $panel
     * @return array
     * @throws DefaultException
     */
    public static function getData($certificate, $panel)
    {
        return [
            'expected' => self::getExpectedRecords($certificate),
            'created' => self::hasRecord($certificate, $panel),
        ];
    }

    /**
     * @param Certificate $certificate
     * @return array
     * @throws Exception
     */
    public static function getExpectedRecords($certificate)
    {
        $domain = $certificate->getDomain();
        $sid = $certificate->getSid();
        $type = $certificate->getType();
        $hashes = ValidationManage::getHashes($sid);

        if ($type === 'default') {
            $records = ["CNAME" => self::generateVerificationCname($domain, $hashes)];
        } elseif ($type == 'ee') {
            $records = [
                "TXT" => [
                    'name' => $domain,
                    'value' => $certificate->authenticationKey($certificate->info()),
                ],
            ];
        }

        return $records;
    }

    /**
     * @param Certificate $certificate
     * @param Manage $panel
     * @return bool
     * @throws Exception
     */
    public static function hasRecord($certificate, $panel)
    {
        try {
            return DNSManage::checkIfExists($panel, $certificate->getDomain(), self::getExpectedRecords($certificate));
        } catch (Exception $e) {
            return false;
        }
    }

    public static function getDomainByCsr($csr)
    {
        $decodedCSR = openssl_csr_get_subject($csr);

        if (!empty($decodedCSR['CN'])) {
            return $decodedCSR['CN'];
        }
    }


    /**
     * @param Manage $panel
     * @return array
     * @throws Exception
     */
    public static function generateRecord(array $certificate, $panel)
    {
        try {
            $result = DNSManage::addRecord($panel, $certificate['commonName'], $certificate['validations']['dcv']);

            return [
                'result' => $result,
                'status' => 'success',
                'message' => $result,
            ];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Generate CNAME record for comodo SSL Verification
     * more info https://www.xolphin.com/support/ssl/Validation/Comodo_Domain_Control_Validation
     */
    private static function generateVerificationCname(string $domain, array $hashes): array
    {
        $sha256 = implode(".", str_split(strtoupper($hashes['sha256']), 32));
        $name = strtoupper(sprintf("_%s.%s", $hashes['md5'], $domain));
        $value = strtoupper(sprintf("%s.comodoca.com", $sha256));

        return ['name' => $name, 'value' => $value];
    }
}
