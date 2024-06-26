<?php

class Modules_RealtimeRegisterSsl_ApiRpc extends pm_Hook_ApiRpc
{
    public function call($data)
    {
        pm_Log::info(__METHOD__);
        pm_Log::info( var_export($data, true));

        $log = fopen('/tmp/realtimegister.txt', 'a+');

        fwrite($log, var_export($data, true));

        fwrite($log, 'username: ' . posix_getuid());
        $pathInfo = pathinfo($data['install_file_validation']['file']['filename']);

        fwrite($log, var_export($pathInfo, true));

        if (!is_dir($pathInfo['dirname'])) {
            fwrite($log, 'dir does not exist');
            $res = mkdir($pathInfo['dirname'], 0775, true);

            if ($res) {
                fwrite($log, 'created new directory');
            } else {
                fwrite($log, 'I did not create a new directory');
            }
        } else {
            fwrite($log, 'dir does exist');
        }

        $r = fopen($data['install_file_validation']['file']['filename'], 'a+');
        fwrite($r, $data['install_file_validation']['file']['content']);
        fclose($r);
        fclose($log);

    }
}
