<?php

namespace AddonModule\RealtimeRegisterSsl\eHelpers;

use ZipArchive;

class ZipFileHelper
{
    private string $zipData;

    public function __construct(string $zipData)
    {
        $this->zipData = $zipData;
    }

    /**
     * @throws \Exception
     */
    public function getFile(string $filename): bool | string
    {
        $zip = new ZipArchive();
        $tmpZipfile = tempnam(sys_get_temp_dir(), 'rtr_ssl_crt');

        file_put_contents($tmpZipfile, $this->zipData);

        if ($zip->open($tmpZipfile) === true) {
            if ($zip->locateName($filename) !== false) {
                return $zip->getFromName($filename);
            }
        }

        throw new \Exception("Could not open file '{$filename}'.");
    }

    public function getNamesOfFilesInDirectory(string $directory): array
    {
        $zip = new ZipArchive();
        $tmpZipfile = tempnam(sys_get_temp_dir(), 'rtr_ssl_crt');

        file_put_contents($tmpZipfile, $this->zipData);

        $files = [];
        if ($zip->open($tmpZipfile) === true) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);

                // Check if the file is inside the target directory
                if (str_starts_with($filename, $directory) && !str_ends_with($filename, '/')) {
                    $files[] = $filename;
                }
            }
        }
        $zip->close();

        return $files;
    }
}
