<?php

namespace AddonModule\RealtimeRegisterSsl\models\whmcs\service\configOptions;

use AddonModule\RealtimeRegisterSsl\eServices\ConfigurableOptionService;
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
     * Mozna by bylo dodac wersje z wczytywanie po samym productid
     *
     * @param type $accountID
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

    public function getID($name)
    {
        if (isset($this->_configOptions[$name])) {
            return $this->_configOptions[$name]->id;
        }
    }

    public function getConfigID($name)
    {
        if (isset($this->_configOptions[$name])) {
            return $this->_configOptions[$name]->configid;
        }
    }

    public function getOptionID($name)
    {
        if (isset($this->_configOptions[$name])) {
            return $this->_configOptions[$name]->optionid;
        }
    }

    public function getFrendlyName($name)
    {
        if (isset($this->_configOptions[$name])) {
            return $this->_configOptions[$name]->frendlyName;
        }
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
                ,S.id as suboptionid
                ,S.optionname as suboptionname
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
            LEFT JOIN
                tblproductconfigoptionssub S
                ON
                    S.configid = O.id
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

            if (isset($this->_configOptions[$name])) {
                $field = $this->_configOptions[$name];
            }

            $field = new ConfigOption();
            $field->id = $row['id'];
            $field->configid = $row['configid'];
            $field->optionid = $row['optionid'];
            $field->name = $name;
            $field->type = $row['optiontype'];
            $field->frendlyName = $friendlyName;

            $tmp = explode('|', $row['suboptionname']);

            $value = $valueLabel = $tmp[0];

            if (isset($tmp[1])) {
                $valueLabel = $tmp[1];
            }

            switch ($row['optiontype']) {
                case 1:
                case 2:
                    $field->optionsIDs[$value] = $row['suboptionid'];
                    $field->options[$value] = $valueLabel;

                    if ($row['suboptionid'] == $row['optionid'] && empty($field->value)) {
                        $field->value = $value;
                    }
                    break;
                case 3:
                case 4:
                    $field->value = $row['qty'];
                    $field->value = $row['qty'];
                    break;
            }

            $this->_configOptions[$field->name] = $field;
        }
    }

    /**
     * Update Custom Fields
     *
     */
    public function update()
    {
        $pid = Query::select(['packageid'], 'tblhosting', ['id' => $this->serviceID])->fetchColumn('packageid');
        $pname = Query::select(['name'], 'tblproducts', ['id' => $pid])->fetchColumn('name');
        ConfigurableOptionService::createForProduct($pid, $pname);

        foreach ($this->_configOptions as $field) {
            $cols = [];

            switch ($field->type) {
                case 1:
                case 2:
                    $cols['optionid'] = $field->optionsIDs[$field->value];
                    break;
                case 3:
                case 4:
                    $cols['qty'] = $field->value;
                    break;
            }

            Query::update(
                'tblhostingconfigoptions',
                $cols,
                [
                    'id' => $field->id
                ]
            );
        }
    }
}
