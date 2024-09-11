<?php

namespace MGModule\RealtimeRegisterSsl\models\whmcs\product\configOptions;

use MGModule\RealtimeRegisterSsl as main;
use MGModule\RealtimeRegisterSsl\mgLibs\MySQL\Query;

/**
 * Product Custom Fields Colletion
 *
 */
class Repository
{
    private $_groups = [];
    private $_assignedids = [];
    private static $configuration;

    public function __construct($productIds)
    {
        if (!empty($productIds)) {
            $this->assigedToProduct($productIds);
            $this->get();
        }
    }

    public static function setConfiguration(array $configuration)
    {
        self::$configuration = $configuration;
    }

    public function assigedToProduct($productIds)
    {
        if (is_array($productIds)) {
            $this->_assignedids = [];
            foreach ($productIds as $pid) {
                $this->_assignedids[] = (int)$pid;
            }
        } else {
            $this->_assignedids[] = (int)($productIds);
        }
    }

    /**
     * Load Product Custom Fields
     *
     * @param int $productID
     */
    public function get()
    {
        $sql = "
            SELECT
                id
                ,name
                ,description
                ,pid 
            FROM
                tblproductconfiggroups G
            LEFT JOIN
                tblproductconfiglinks L
                ON
                    L.gid = G.id
        ";

        $condition = [];

        if ($this->_assignedids) {
            $condition = "L.pid in (" . implode(',', $this->_assignedids) . ")";
        }

        if ($condition) {
            $sql .= " WHERE " . $condition;
        }

        $result = Query::query($sql);

        while ($row = $result->fetch()) {
            if (isset($this->_groups[$row['id']])) {
                $this->_groups[$row['id']]->addPID($row['pid']);
            } else {
                $this->_groups[$row['id']] = new group($row['id'], $row);
            }
        }

        return $this->_groups;
    }


    /**
     * Compare current Fields with Declaration from Module Configuration
     *
     * @param bool $onlyRequired
     * @return array
     */
    public function checkFields(array $configuration = [])
    {
        if (empty($configuration)) {
            $configuration = self::$configuration;
        }

        $missingFields = [];

        foreach ($configuration as $fieldDeclaration) {
            $found = false;
            foreach ($this->_groups as $field) {
                if ($fieldDeclaration->name === $field->name) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $name = (empty($fieldDeclaration->friendlyName)) ? $fieldDeclaration->name
                    : $fieldDeclaration->friendlyName;
                $missingFields[$fieldDeclaration->name] = $name;
            }
        }

        return $missingFields;
    }


    /**
     * Generate Custom Fields Depends on declaration in Module Configuration
     *
     */
    public function generateFromConfiguration(array $configuration = [])
    {
        if (empty($configuration)) {
            $configuration = self::$configuration;
        }

        foreach ($configuration as $fieldDeclaration) {
            $found = false;
            foreach ($this->_groups as $field) {
                if ($fieldDeclaration->name === $field->name) {
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                //$fieldDeclaration->a;

                //$field->save();
                //$this->_groups[] = $field;
            }
        }
    }
}
