<?php

namespace AddonModule\RealtimeRegisterSsl\models\whmcs\service\configOptions;

use AddonModule\RealtimeRegisterSsl\addonLibs\MySQL\Query;

/**
 * Description of repository
 *
 * @SuppressWarnings(PHPMD)
 */
class Repository
{
    private $serviceID;

    /**
     *
     * @var configOption[]
     */
    public $_configOptions = [];

    /**
     * Construct by service id
     *
     * @param type serviceID
     */
    public function __construct($serviceID, array $data = [])
    {
        $this->serviceID = $serviceID;

        if (!empty($data)) {
            $this->load();
            foreach ($data as $name => $value) {
                $this->_configOptions[$name]->value = $value;
            }
        } else {
            $this->load();
        }
    }

    public function __isset($name)
    {
        return $this->_configOptions[$name];
    }

    public function __get($name)
    {
        if (isset($this->_configOptions[$name])) {
            return $this->_configOptions[$name]->value;
        }
    }

    public function __set($name, $value)
    {
        if (isset($this->_configOptions[$name])) {
            $this->_configOptions[$name]->value = $value;
        }
    }

    public function getID(string $name, int $period)
    {
        $configOption = $this->getConfigOption($name, $period);
        return $configOption?->id;
    }

    public function getConfigID(string $name, int $period)
    {
        $configOption = $this->getConfigOption($name, $period);
        return $configOption?->configid;
    }

    public function getOptionID(string $name, int $period): ?int
    {
        $configOption = $this->_configOptions[$name] ?? $this->_configOptions[$name . $period];
        if (!$configOption) {
            return null;
        }
        if (count($configOption->options) == 1) {
            return array_key_first($configOption->options);
        }
        foreach ($configOption->options as $optionId => $optionName) {
            if (str_contains($optionName, strval($period))) {
                return $optionId;
            }
        }
        return null;
    }

    public function getFriendlyName(string $name, int $period): ?string
    {
        $configOption = $this->getConfigOption($name, $period);
        return $configOption?->friendlyName;
    }

    public function load()
    {
        $query = "
            SELECT
                V.id
                ,V.optionid
                ,V.qty
                ,V.configid
                ,O.optionname
                ,O.optiontype
            FROM
                tblhostingconfigoptions V
            JOIN
                tblproductconfigoptions O
                ON
                    V.configid = O.id
            JOIN
                tblproductconfiglinks L
                ON
                    L.gid = O.gid
            JOIN
                tblhosting H
                ON
                    H.packageid = L.pid
                    AND H.id = V.relid
            WHERE
                H.id = $this->serviceID
        ";


        $result = Query::query($query);

        while ($row = $result->fetch()) {
            $tmp = explode('|', $row['optionname']);

            $name = $friendlyName = $tmp[0];

            if (isset($tmp[1])) {
                $friendlyName = $tmp[1];
            }

            $field = new ConfigOption();
            $field->id = $row['id'];
            $field->configid = $row['configid'];
            $field->optionid = $row['optionid'];
            $field->name = $name;
            $field->type = $row['optiontype'];
            $field->friendlyName = $friendlyName;

            $subOptions = Query::query("SELECT s.id, s.optionname FROM 
                              tblproductconfigoptionssub s
                              WHERE s.configid = :configid", ["configid" => $field->configid]);

            while ($subOption = $subOptions->fetch()) {
                $field->options[$subOption['id']] = $subOption['optionname'];
                if ($row['optiontype'] == 4) {
                    $field->value = $row['qty'];
                }
            }

            $this->_configOptions[$field->name] = $field;
        }
    }

    private function getConfigOption(string $name, int $period) {
        return $this->_configOptions[$name] ?? $this->_configOptions[$name . $period] ?? null;
    }
}
