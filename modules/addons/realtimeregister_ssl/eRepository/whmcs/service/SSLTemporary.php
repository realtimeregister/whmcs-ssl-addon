<?php

namespace AddonModule\RealtimeRegisterSsl\eRepository\whmcs\service;

class SSLTemporary
{
    private static $instance;
    private $ssl = [];

    /**
     * @return SSLTemporary
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new SSLTemporary();
        }
        return self::$instance;
    }

    public function setByParams(&$params)
    {
        if ($params['moduletype'] !== 'realtimeregister_ssl') {
            return;
        }
        $this->set($_GET['cert'], true);
    }

    public function set($md5, $conf)
    {
        $this->ssl[$md5] = $conf;
    }

    public function get($md5)
    {
        return $this->ssl[$md5];
    }
}
