<?php

namespace AddonModule\RealtimeRegisterSsl\models\whmcs\service\configOptions;

use WHMCS\Database\Capsule;

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
        $result = Capsule::table("tblhostingconfigoptions as hco")
            ->select('hco.id', 'hco.optionid', 'hco.qty', 'hco.configid', 'pco.optionname', 'pco.optiontype')
            ->join('tblproductconfigoptions AS pco', 'hco.configid', '=', 'pco.id')
            ->join('tblproductconfiglinks', 'tblproductconfiglinks.gid', '=', 'pco.gid')
            ->join('tblhosting', function($join) {
                $join->on('tblhosting.packageid', '=', 'tblproductconfiglinks.pid');
                $join->on('tblhosting.id', '=', 'hco.relid');
            })
            ->where('hco.id', '=', $this->serviceID)
            ->get();

        foreach ($result as $row) {
            $tmp = explode('|', $row->optionname);

            $name = $friendlyName = $tmp[0];

            if (isset($tmp[1])) {
                $friendlyName = $tmp[1];
            }

            $field = new ConfigOption();
            $field->id = $row->id;
            $field->configid = $row->configid;
            $field->optionid = $row->optionid;
            $field->name = $name;
            $field->type = $row->optiontype;
            $field->friendlyName = $friendlyName;
            $this->_configOptions[$field->name] = $field;
        }
    }

    private function getConfigOption(string $name) {
        return $this->_configOptions[$name] ?? null;
    }
}
