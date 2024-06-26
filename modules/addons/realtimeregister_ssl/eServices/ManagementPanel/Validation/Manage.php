<?php

declare(strict_types=1);

namespace MGModule\RealtimeRegisterSsl\eServices\ManagementPanel\Validation;

use HostcontrolSSL\Service\Certificate;
use Illuminate\Database\Capsule\Manager as Capsule;
use MGModule\RealtimeRegisterSsl\eServices\ManagementPanel\Api\Panel\Exceptions\SSLValidationException;
use MGModule\RealtimeRegisterSsl\eServices\ManagementPanel\Dns\DnsControl;
use MGModule\RealtimeRegisterSsl\eServices\ManagementPanel\File\FileControl;

class Manage
{
    /**
     * @param $domain
     * @param null|Certificate $certificate
     * @return array
     * @throws \Exception
     */
    public static function getValidationStatus($domain, $certificate = null)
    {
        if ($certificate == null) {
            /** @var Certificate $certificate */
            $certificate = self::getCertificate($domain);
        }
        $error = null;
        $info = $certificate->info();

        $panel = new \MGModule\RealtimeRegisterSsl\eServices\ManagementPanel\Api\Panel\Manage($domain);

        try {
            $panelData = $panel->getPanelData();

            $panelVars = [
                'status'   => $panelData['status'],
                'platform' => $panelData['platform'],
                'isActive' => $panel->isPanelActive(),
            ];
        } catch (\Exception $e) {
            $panelVars = [
                'isActive' => false,
            ];
        }
        try {
            $dvcData = self::getDvcData($certificate, $panel);
        } catch (\Exception $e) {
            $panelVars['isActive'] = false;
            $error = $e->getMessage();
        }
        if($dvcData['created'] === null) {
            $panelVars['isActive'] = false;
        }

        return [
            'validationStatus' => $info['status'],
            'dvc'              => $info['dcv_type'],
            'dvcData'          => $dvcData,
            'panel'            => $panelVars,
            'error'            => $error,
        ];
    }

    public static function createValidationRequire($domain, $certificate = null)
    {
        if ($certificate == null) {
            /** @var Certificate $certificate */
            $certificate = self::getCertificate($domain);
        }

        $status = self::getValidationStatus($domain, $certificate);
        if (!$status['panel']['isActive']) {
            throw new SSLValidationException("Panel not active", 10);
        }
        if ($status['dvcData']['created']) {
            throw new SSLValidationException("Records Already Created", 11);
        }

        $panel = new \MGModule\RealtimeRegisterSsl\eServices\ManagementPanel\Api\Panel\Manage($domain);

        if ($status['dvc'] == "DNS") {
            return DnsControl::generateRecord($certificate, $panel);
        } elseif ($status['dvc'] == "FILE") {
            return FileControl::create($certificate, $panel);
        } elseif ($status['dvc'] == "EMAIL") {
            return ['status' => 'success'];
        }
    }

    public static function downloadFile($domain, $certificate = null)
    {
        if ($certificate == null) {
            /** @var Certificate $certificate */
            $certificate = self::getCertificate($domain);
        }

        FileControl::downloadFile($certificate);
    }

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
     * @param \MGModule\RealtimeRegisterSsl\eServices\ManagementPanel\Api\Panel\Manage $panel
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

    /**
     * @param string $domain
     * @return \HostcontrolSSL\Service\Certificate
     */
    private static function getCertificate($domain)
    {
        $manage = new \MGModule\RealtimeRegisterSsl\eServices\ManagementPanel\Api\Panel\Manage(['domain' => $domain]);

        /** @var Certificate $certificate */
        return $manage->service('certificate');
    }
}
