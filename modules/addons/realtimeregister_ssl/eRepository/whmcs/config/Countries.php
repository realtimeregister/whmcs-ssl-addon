<?php

namespace AddonModule\RealtimeRegisterSsl\eRepository\whmcs\config;

use Exception;

class Countries
{
    private static $instance;
    private $countries = [];

    /**
     * 
     * @return Countries
     */
    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new Countries();
        }
        return self::$instance;
    }

    function __construct()
    {
        $ccPath = \AddonModule\RealtimeRegisterSsl\eProviders\PathProvider::getWhmcsCountriesPatch();

        if (!file_exists($ccPath)) {
            throw new Exception('Countries file not exist');
        }

        $countries = json_decode(file_get_contents($ccPath));

        if (is_null($countries)) {
            throw new Exception('Can not decode countries JSON');
        }

        foreach ($countries as $countryCode => $country) {
            $this->countries[$countryCode] = str_replace(',', ' -', $country->name);
        }
    }
    /**
     * 
     * @throws Exception
     */
    public function getCountryCodeByName(string $name): string
    {
        if (strlen($name) <= 2) {
            return $name;
        }
        
        foreach ($this->countries as $countryCode => $countryName) {
            if (strtolower($countryName) === strtolower($name)) {
                return $countryCode;
            }
        }
        throw new Exception('Can not match country name to country code');
    }

    /**
     * 
     * @throws Exception
     */
    public function getCountryNameByCode(string $code): string
    {
        $code = strtoupper($code);
        
        if (isset($this->countries[$code])) {
            return $this->countries[$code];
        }

        throw new Exception('Can not match country code to country name');
    }
    
    public function getCountriesForWhmcsDropdownOptions()
    {
        return implode(',', $this->countries);
    }
    
    public function getCountriesForAddonDropdown()
    {
        return $this->countries;
    }
}
