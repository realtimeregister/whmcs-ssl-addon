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
    private $_configOptions = [];

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

    public function getID(string $name)
    {
        return $this->getConfigOption($name)?->id;
    }

    public function getConfigID(string $name)
    {
        return $this->getConfigOption($name)?->configid;
    }

    public function getOptionID(string $name): ?int
    {
        return $this->getConfigOption($name)?->optionid;
    }

    public function getFriendlyName(string $name): ?string
    {
        return $this->getConfigOption($name)?->friendlyName;
    }

    public function load() : void
    {
        $query = "
            SELECT V.id, V.optionid, V.qty, V.configid, O.optionname, O.optiontype
            FROM tblhostingconfigoptions V
            JOIN tblproductconfigoptions O ON V.configid = O.id
            JOIN tblproductconfiglinks L ON L.gid = O.gid
            JOIN tblhosting H ON H.packageid = L.pid AND H.id = V.relid
            WHERE H.id = $this->serviceID
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
            $this->_configOptions[$field->name] = $field;
        }
    }

    private function getConfigOption(string $name) {
        return $this->_configOptions[$name] ?? null;
    }
}
