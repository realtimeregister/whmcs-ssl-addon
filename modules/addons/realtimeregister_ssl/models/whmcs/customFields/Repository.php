<?php

namespace MGModule\RealtimeRegisterSsl\models\whmcs\customFields;

/**
 * Product Custom Fields Colletion
 *
 */
class Repository
{
    public $relationID;
    public $type;
    private $_fields = [];
    static private $configuration;

    static function setConfiguration(array $configuration)
    {
        self::$configuration = $configuration;
    }

    /**
     * Load Product Custom Fields
     *
     * @param int $productID
     */
    public function __construct($type, $relationID)
    {
        $this->type = $type;
        $this->relationID = $relationID;
        $result = \MGModule\RealtimeRegisterSsl\mgLibs\MySQL\Query::select(
            customField::fieldDeclaration(),
            customField::tableName(),
            [
                'relid' => $this->relationID,
                'type' => $this->type
            ]
        );

        while ($row = $result->fetch()) {
            $this->_fields[] = new customField($row['id'], $this->type, $this->relationID, $row);
        }
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
            foreach ($this->_fields as $field) {
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
            foreach ($this->_fields as $field) {
                if ($fieldDeclaration->name === $field->name) {
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $fieldDeclaration->save();
                $this->_fields[] = $fieldDeclaration;
            }
        }
    }

    /**
     *
     * @return customField[]
     */
    public function get()
    {
        return $this->_fields;
    }
}
