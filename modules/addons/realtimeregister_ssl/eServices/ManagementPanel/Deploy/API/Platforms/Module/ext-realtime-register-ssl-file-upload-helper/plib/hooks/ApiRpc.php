<?php

class Modules_RealtimeRegisterSsl_ApiRpc extends pm_Hook_ApiRpc
{
    public function call($data)
    {
        $pathInfo = pathinfo($data['install_file_validation']['file']['filename']);

        $domain = pm_Domain::getByName($data['install_file_validation']['file']['domain']);

        $fileManager = new pm_FileManager($domain->getId());

        if (!is_dir($pathInfo['dirname'])) {
            $fileManager->mkdir($pathInfo['dirname'], '0755', true);
        }

        $fileManager->filePutContents(
            $pathInfo['dirname'] . DIRECTORY_SEPARATOR . $pathInfo['basename'],
            $data['install_file_validation']['file']['content']
        );
    }
}
