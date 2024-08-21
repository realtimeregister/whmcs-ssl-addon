<?php

declare(strict_types=1);

namespace MGModule\RealtimeRegisterSsl\eServices\ManagementPanel\File;

use Exception;
use MGModule\RealtimeRegisterSsl\eServices\ManagementPanel\Validation\Manage as ValidationManage;
use MGModule\RealtimeRegisterSsl\eServices\ManagementPanel\Api\File\Manage;
use MGModule\RealtimeRegisterSsl\eServices\ManagementPanel\Api\File\Manage as FileManage;

class FileControl
{
    /**
     * @param Certificate $certificate
     * @param Manage $panel
     * @return array
     * @throws \Exception
     */
    public static function getData($certificate, $panel)
    {
        try {
            $created = self::hasRecord($certificate, $panel);
        } catch (Exception $e) {
            if ($e->getCode() == 12) {
                $created = null;
            }
            $created = false;
        }

        return [
            'expected' => self::getExpected($certificate),
            'created' => $created,
        ];
    }

    /**
     * @param \MGModule\RealtimeRegisterSsl\eServices\ManagementPanel\Api\Panel\Panel $panel
     * @return array
     * @throws Exception
     */
    public static function create(array $dcvData, $panel): array
    {
        /** @noinspection HttpUrlsUsage */
        if (!strpos($dcvData['fileLocation'], 'https://') && !strpos($dcvData['fileLocation'], 'http://') ) {
            $dcvData['fileLocation'] = 'https://' . $dcvData['fileLocation'];
        }
        $url = parse_url($dcvData['fileLocation']);
        $pathInfo = pathinfo($url['path']);
        $file = [
            'dir' => $pathInfo['dirname'],
            'name' => $pathInfo['basename'],
            'content' => $dcvData['fileContents'],
        ];

        FileManage::sendFile($panel, $file, $file['dir']);
        return [
            'status' => 'success',
            'message' => 'Successfully loaded',
        ];
    }

    /**
     * @param Certificate $certificate
     * @return array
     * @throws Exception
     */
    public static function getExpected($certificate)
    {
        $sid = $certificate->getSid();
        $type = $certificate->getType();

        if ($type == 'ee') {
            try {
                $key = $certificate->authenticationKey($certificate->info());
            } catch (Exception $e) {
                return null;
            }
            $file['dir'] = '.well-known/pki-validation/';
            $file['name'] = 'fileauth.txt';
            $file['content'] = $key;
        } else {
            $hashes = ValidationManage::getHashes($sid);
            $file['dir'] = '.well-known/pki-validation/';
            $file['name'] = strtoupper($hashes['md5']) . ".txt";
            $file['content'] = self::getFileContent($hashes);
        }

        return $file;
    }

    /**
     * @param $certificate
     * @param $panel
     * @return array
     * @throws Exception
     */
    public static function hasRecord($certificate, $panel)
    {
        $file = self::getExpected($certificate);

        return FileManage::checkIfExists($panel, $file);
    }


    /**
     *
     * @param Certificate $certificate
     * @throws Exception
     */
    public static function downloadFile($certificate)
    {
        $file = self::getExpected($certificate);

        header('Content-Description: File Transfer');
        header(sprintf('Content-disposition: attachment; filename=%s', $file['name']));
        header('Content-type: text/plain');
        print $file['content'];
        exit;
    }

    /**
     * @param $hashes
     * @return string
     */
    public static function getFileContent($hashes)
    {
        return sprintf("%s\n", $hashes['sha256']) . "comodoca.com";
    }
}
