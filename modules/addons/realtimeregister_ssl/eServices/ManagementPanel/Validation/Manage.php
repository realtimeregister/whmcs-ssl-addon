<?php

declare(strict_types=1);

namespace AddonModule\RealtimeRegisterSsl\eServices\ManagementPanel\Validation;

use Illuminate\Database\Capsule\Manager as Capsule;
use AddonModule\RealtimeRegisterSsl\eServices\ManagementPanel\Api\Panel\Exceptions\SSLValidationException;
use AddonModule\RealtimeRegisterSsl\eServices\ManagementPanel\Dns\DnsControl;
use AddonModule\RealtimeRegisterSsl\eServices\ManagementPanel\File\FileControl;
use RealtimeRegister\Domain\Certificate;

class Manage
{
    /**
     * @param int $sid
     * @return array
     * @throws \Exception
     */
    public static function getHashes($sid)
    {
        $csr = self::getCsrByServiceId($sid);
        if (!$csr) {
            throw new SSLValidationException("Can't load csr");
        }
        // Convert form PEM to DER
        $pemStart = "REQUEST-----";
        $pemEnd = "-----END";
        $csr = substr($csr, strpos($csr, $pemStart) + strlen($pemStart));
        $csr = substr($csr, 0, strpos($csr, $pemEnd));

        $der = base64_decode($csr);
        // generate hashes
        $md5 = md5($der);
        $sha256 = hash("sha256", $der);

        return [
            'md5'    => $md5,
            'sha256' => $sha256,
        ];
    }

    /**
     * Return CSR from db by Service (Hosting) Id
     *
     * @param $sid
     * @return string
     */
    public static function getCsrByServiceId($sid)
    {
        $info = Capsule::table("tblcustomfields")
            ->join("tblcustomfieldsvalues", "tblcustomfields.id", "=", "tblcustomfieldsvalues.fieldid")
            ->where("tblcustomfields.type", "product")
            ->where("tblcustomfields.fieldname", "csr|CSR Information")
            ->where("tblcustomfieldsvalues.relid", $sid)
            ->first(['tblcustomfieldsvalues.value']);

        return $info->value;
    }

    public static function getDomainByCsr($csr)
    {
        $decodedCSR = openssl_csr_get_subject($csr);

        if (!empty($decodedCSR['CN'])) {
            return $decodedCSR['CN'];
        }

        return;
    }

    /**
     * @param Certificate $certificate
     * @param \AddonModule\RealtimeRegisterSsl\eServices\ManagementPanel\Api\Panel\Manage $panel
     * @return array
     * @throws \Exception
     */
    private static function getDvcData($certificate, $panel)
    {
        $info = $certificate->info();
        $dvc = $info['dcv_type'];

        if ($dvc == "DNS") {
            return DnsControl::getData($certificate, $panel);
        } elseif ($dvc == "FILE") {
            return FileControl::getData($certificate, $panel);
        } elseif ($dvc == "EMAIL") {
            return [
                'expected' => self::getEmails($certificate),
            ];
        }
    }

    /**
     * @param Certificate $certificate
     * @param string $domain
     * @return array
     */
    public static function getEmails($certificate = null, $domain = null)
    {
        if($domain == null) {
            $domain = $certificate->getDomain();
        }

        $emails = [
            'admin',
            'webmaster',
            'administrator',
            'web',
            'root',
        ];
        $addresses = [];

        foreach ($emails as $name) {
            $addresses[] = sprintf("%s@%s", $name, $domain);
        }

        return $addresses;
    }
}
