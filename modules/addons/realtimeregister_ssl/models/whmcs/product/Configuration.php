<?php

namespace MGModule\RealtimeRegisterSsl\models\whmcs\product;

use MGModule\RealtimeRegisterSsl\mgLibs\exceptions\System;
use MGModule\RealtimeRegisterSsl\mgLibs\MySQL\Query;
use MGModule\RealtimeRegisterSsl\mgLibs\process\MainInstance;

/**
 * Description of configuration
 *
 * @author Michal Czech <michael@modulesgarden.com>
 * @SuppressWarnings(PHPMD)
 */
class Configuration
{
    private $_productID;
    private $_configuration;
    /**
     * @var array
     */
    private static $_configurationArray;

    public static function setDefaultConfigurationArray($configurationArray)
    {
        self::$_configurationArray = $configurationArray;
    }

    public function __construct($productID, array $params = [])
    {
        if (empty($productID)) {
            throw new System('Provide Product ID at first');
        }

        $this->_productID = $productID;

        if (!isset($params['configoption1'])) {
            $params = $this->getConfigOptions();
        }

        if (empty(self::$_configurationArray)) {
            $mainConfig = MainInstance::I()->configuration();

            if (method_exists($mainConfig, 'getServerWHMCSConfig')) {
                $config = $mainConfig->getServerWHMCSConfig();
                if (is_array($config)) {
                    self::$_configurationArray = $config;
                }
            }
        }

        $i = 1;

        if (is_array(self::$_configurationArray) && !empty(self::$_configurationArray)) {
            foreach (self::$_configurationArray as $name) {
                $this->_configuration[$name] = $params['configoption' . $i];
                $i++;
            }
        } else {
            for ($i = 1; $i < 25; $i++) {
                $this->_configuration[$i] = $params['configoption' . $i];
            }
        }
    }

    public function getConfigOptions() {
        $fields = [];
        for ($i = 1; $i < 25; $i++) {
            $fields['configoption' . $i] = 'configoption' . $i;
        }

        return Query::select($fields, 'tblproducts', [
            'id' => $this->_productID
        ])->fetch();
    }

    public function setConfigurationArray(array $configurationArray = [])
    {
        if (empty($configurationArray)) {
            $configurationArray = self::$_configurationArray;
        }

        $i = 1;
        foreach ($configurationArray as $name) {
            if (isset($this->_configuration[$i])) {
                $this->_configuration[$name] = $this->_configuration[$i];
                unset($this->_configuration[$i]);
            }

            $i++;
        }
    }

    public function __set($name, $value)
    {
        $this->_configuration[$name] = $value;
    }


    public function __get($name)
    {
        return $this->_configuration[$name];
    }

    public function __isset($name)
    {
        return isset($this->_configuration[$name]);
    }

    public function save()
    {
        $params = array();

        if (self::$_configurationArray) {
            $i = 1;
            foreach (self::$_configurationArray as $name) {
                $params['configoption' . $i] = $this->_configuration[$name];
                $i++;
            }
        } else {
            for ($i = 1; $i < 25; $i++) {
                $params['configoption' . $i] = $this->_configuration[$i];
                $i++;
            }
        }

        Query::update('tblproducts', $params, [
            'id' => $this->_productID
        ]);
    }
}
