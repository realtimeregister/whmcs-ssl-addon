<?php

namespace AddonModule\RealtimeRegisterSsl\addonLibs\models;
use AddonModule\RealtimeRegisterSsl as main;

/**
 * Description of ORMHelper
 *
 * @SuppressWarnings(PHPMD)
 */
class Orm extends Base
{
    static $tableStructure;

    function __construct($id = false,$data = [])
    {
        if ($id !== false )
            $this->id = (int) $id;

        if ($id !== false && empty($data)) {
            $data = $this->getRawData($id);
        }

        if (!empty($data) && is_array($data)) {
            $this->fillProperties($data);
        }
    }

    static function getTableStructure($force = false)
    {
        $class = get_called_class();
        if (!empty(static::$tableStructure[$class]) && $force === false) {
            return static::$tableStructure[$class];
        }
        
        static::$tableStructure[$class] = [
            'prefixed'  => true
        ];

        $rc = new \ReflectionClass(get_called_class());

        if (!preg_match("/@Table\((.*)\)/D", $rc->getDocComment())) {
            $rc = new \ReflectionClass(get_parent_class(get_called_class()));
        }
        
        if (preg_match("/@Table\((.*)\)/D", $rc->getDocComment(),$result)) {
            foreach(explode(',',$result[1]) as $setting) {
                $tmp = explode('=',$setting);
                if (isset($tmp[1])) {
                    if ($tmp[1] == 'false') {
                        $value = false;
                    } elseif ($tmp[1] == 'true') {
                        $value = true;
                    } else {
                        $value = $tmp[1];
                    }
                    
                    static::$tableStructure[$class][$tmp[0]] = $value;
                } else {
                    static::$tableStructure[$class][$tmp[0]] = true;
                }
            }
        }
                
        foreach ($rc->getProperties() as $property) {
            $configs = [];
            if (preg_match("/@Column\((.*)\)/D", $property->getDocComment(),$result)) {
                foreach (explode(',',$result[1]) as $setting) {
                    $tmp = explode('=',$setting);
                    if (isset($tmp[1])) {
                        if ($tmp[1] == 'false') {
                            $configs[$tmp[0]] = false;
                        } elseif  ($tmp[1] == 'true') {
                            $configs[$tmp[0]] = true;
                        } else {
                            $configs[$tmp[0]] = $tmp[1];
                        }
                    } else {
                        $configs[$tmp[0]] = true;
                    }
                }
            }
            
            if ($configs) {
                if (!isset($configs['as'])) {
                    $configs['as'] = $property->name;
                }
                if (!isset($configs['name'])) {
                    $configs['name'] = preg_replace_callback(
                        '/([A-Z])/D',
                        function($params){
                            return '_'.strtolower($params[0]);
                        },
                        $property->name
                    );
                }
                
                static::$tableStructure[$class]['columns'][$property->name] = $configs;
            }
            
            $configs = [];
            if (preg_match("/@Validation\((.*)\)/D", $property->getDocComment(),$result)) {
                foreach (explode(',',$result[1]) as $setting) {
                    $tmp = explode('=',$setting);
                    if (isset($tmp[1])) {
                        if ($tmp[1] == 'false') {
                            $configs[$tmp[0]] = false;
                        } elseif ($tmp[1] == 'true') {
                            $configs[$tmp[0]] = true;
                        } else {
                            $configs[$tmp[0]] = $tmp[1];
                        }
                    } else {
                        $configs[$tmp[0]] = true;
                    }
                }
            }
            
            if ($configs) {
                static::$tableStructure[$class]['validation'][$property->name] = $configs;
            }
        }
        
        return static::$tableStructure[$class];
    }
    
    public static function tableName($prefixed = true)
    {
        $tableStructure = self::getTableStructure();

        if ($prefixed && $tableStructure['prefixed']) {
            return self::prefixTable($tableStructure['name']);
        } else {
            return $tableStructure['name'];
        }
    }
    
    public static function fieldDeclaration()
    {
        $tableStructure = self::getTableStructure();
        $output = [];
        foreach ($tableStructure['columns'] as $column) {
            $output[$column['name']] = $column['as'];
        }
        return $output;
    }
    
    static function getProperyColumn($property)
    {
        $tableStructure = self::getTableStructure();

        if (!isset($tableStructure['columns'][$property]['name'])) {
            throw new main\addonLibs\exceptions\System("Property $property not exists");
        }
        
        return $tableStructure['columns'][$property]['name'];
    }
    
    protected function getRawData($id, $haveToExits = true)
    {
        $data = main\addonLibs\MySQL\Query::select(
            self::fieldDeclaration(),
            self::tableName(),
            [
                'id'    => $id
            ]
        )->fetch();

        if (empty($data) && $haveToExits) {
            throw new main\addonLibs\exceptions\System('Unable to find '.  get_class($this).' with ID:'.$id);
        }
        
        return $data;
    }
    
    function save($data = [])
    {
        foreach (self::fieldDeclaration() as $mysqlName => $field) {
            if (isset($data[$field])) {
                continue;
            }
            
            if (!property_exists($this, $field)) {
                continue;
            }
            
            if (is_numeric($mysqlName)) {
                $data[$field] = $this->{$field};
            } else {
                $data[$mysqlName] = $this->{$field};
            }
        }

        if ($this->id) {
            main\addonLibs\MySQL\Query::update(
                self::tableName(),
                $data,
                [
                    'id' => $this->id
                ]
            );
        } else {
            $this->id = main\addonLibs\MySQL\Query::insert(
                self::tableName(),
                $data
            );
        }
    }
    
    function delete()
    {
        if ($this->id) {
            main\addonLibs\MySQL\Query::delete(
                self::tableName(),
                ['id' => $this->id]
            );
        }
    }
    
    function validate()
    {
        $structure = self::getTableStructure();
        
        $errors = [];
        
        foreach ($structure['validation'] as $property => $config) {
            if ($property == 'id') {
                continue;
            }

            if (isset($config['notEmpty']) && empty($this->{$property})) {
                $errors[$property][] = 'emptyField';
            }
        }

        if ($errors) {
            throw new main\addonLibs\exceptions\validation('validateError',$errors);
        }
    }
    
    public static function prefixTable($table)
    {
        return main\addonLibs\process\MainInstance::I()->configuration()->tablePrefix.$table;
    }
    
    /**
     * Fill Current Model Properties 
     *
     * @param array $data
     */
    function fillProperties($data)
    {
        if (empty($data)) {
            return;
        }
        
        $used = ['id'=>true];
        
        if (is_array($data)) {
            foreach ($data as $property => $value) {
                if (property_exists($this, $property)) {
                    $this->$property = $value;
                    $used[$property] = true;
                }
            }
        }
        
        $structure = self::getTableStructure();
        foreach($structure['columns'] as $property => $configs) {
            if(!isset($used[$property]) && !isset($configs['notRequired'])) {
                throw new main\addonLibs\exceptions\System(
                    'Missing object property: '.$property,
                    main\addonLibs\exceptions\Codes::MISSING_OBJECT_PROPERTY
                );
            }
        }
    }
    
    function setProperties($data)
    {
        foreach ($data as $k => $v) {
            $method = 'set'.ucfirst($k);
            if (method_exists($this, $method)){
                $this->$method($v);
                continue;
            }
            throw new main\addonLibs\exceptions\System(
                sprintf('Object property "%s" does not exist ',$k),
                main\addonLibs\exceptions\Codes::MISSING_OBJECT_PROPERTY
            );
        }
    }
}
