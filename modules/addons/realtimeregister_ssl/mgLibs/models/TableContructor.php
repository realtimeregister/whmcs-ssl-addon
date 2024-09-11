<?php

namespace AddonModule\RealtimeRegisterSsl\addonLibs\models;
use AddonModule\RealtimeRegisterSsl as main;

/**
 * Description of tableContructor
 *
 * @SuppressWarnings(PHPMD)
 */
class TableContructor
{
    private $prefix;
    private $engine = 'InnoDB';
    private $charset = 'UTF8';
    private $refrences = [];
    private $mainNameSpace = null;
    private $existsTables = [];
    private $declaredTables = [];

    static $predefinedColumTypes = [
        'id'        => [
            'definition' => 'int(:int:) NOT NULL AUTO_INCREMENT',
            'default'   => [
                'int'           => 11,
                'isPrimaryKey' => true
            ]
        ],
        'varchar' => [
            'definition'    => 'varchar(:varchar:) CHARACTER SET :charset: :null:',
            'default'      => [
                'varchar'      => 128,
                'charset'     => 'utf8',
                'null'        => false
            ]
        ],
        'custom'  =>  [
            'definition'    => ':custom:'
        ],
        'mediumblob'   => [
            'definition'    => 'mediumblob :null:',
            'default'      => [
                'null'        => false
            ]
        ],
        'smallint'     => [
            'definition'    => 'smallint(:smallint:) :null:',
            'default'      => [
                'smallint'     => 6,
                'null'        => false
            ]
        ],
        'tinyint'     => [
            'definition'    => 'tinyint(:tinyint:) :null:',
            'default'      => [
                'tinyint'     => 6,
                'null'        => false
            ]
        ],
        'boolean'     => [
            'definition'    => 'tinyint(:tinyint:) :null:',
            'default'      => [
                'tinyint'     => 1,
                'null'        => false
            ]
        ],
        'datetime'     => [
            'definition'    => 'datetime DEFAULT :default:',
            'default'      => [
                'default'     => 'NULL'
            ]
        ],
        'date'     => [
            'definition'    => 'date DEFAULT :default:',
            'default'      => [
                'default'     => 'NULL'
            ]
        ],
        'int'     => [
            'definition'    => 'int(:int:) :null:',
            'default'      => [
                'int'           => 11,
                'null'         => false
            ]
        ],
        'mediumtext'   => [
            'definition'    => 'mediumtext :null:',
            'default'      => [
                'null'        => false
            ]
        ],
        'text'   => [
            'definition'    => 'text :null:',
            'default'      => [
                'null'        => false
            ]
        ]
    ];
    
    function __construct($namespace,$prefix)
    {
        $this->mainNameSpace    = $namespace;
        $this->prefix           = $prefix;
        
        $result = \AddonModule\RealtimeRegisterSsl\addonLibs\MySQL\Query::query("SHOW Tables");
        while($column = $result->fetchColumn()) {
            $this->existsTables[] = $column;
        }
    }
            
    function createDBModels($models)
    {
        foreach ($models as $model) {
            $class = $this->mainNameSpace."\\".$model;

            if (!class_exists($class)) {
                throw new \AddonModule\RealtimeRegisterSsl\addonLibs\exceptions\System('Model Class Not Exists');
            }

            $structure = $class::getTableStructure();

            $this->createTable($structure);
        }
        
        $this->createRefrences();
    }
    
    function createTable($structure)
    {
        if (isset($structure['preventUpdate'])) {
            return;
        }
        
        if (empty($structure['name'])) {
            throw new \AddonModule\RealtimeRegisterSsl\addonLibs\exceptions\System('Table name is empty');
        }
        
        if (in_array($structure['name'], $this->declaredTables) && !isset($structure['multipleUsage'])) {
            throw new \AddonModule\RealtimeRegisterSsl\addonLibs\exceptions\System('Table declared in other model');
        }
        
        $this->declaredTables[] = $structure['name'];

        $tableName = empty($structure['prefixed'])?$structure['name']:$this->prefix.$structure['name'];
        
        $charset = empty($structure['charset'])?$this->charset:$structure['charset'];
        
        $engine = empty($structure['engine'])?$this->engine:$structure['engine'];
        
        $columns = [];
        $keys = [
            'keys'      => [],
            'primary'  => null
        ];

        $existsColumns = [];
        $addNewColumns = [];
        $updateColumns = [];
        
        if (in_array($tableName, $this->existsTables)) {
            $result = \AddonModule\RealtimeRegisterSsl\addonLibs\MySQL\Query::query("SHOW COLUMNS IN `$tableName`");

            while ($row = $result->fetch()) {
                $existsColumns[$row['Field']] = $row;
            }            
        }
        
        foreach ($structure['columns'] as $data) {
            $type = null;
            $options = [];
            foreach ($data as $name => $value) {
                $options[] = "$name=$value";
                if (isset(self::$predefinedColumTypes[$name])) {
                    $type = $name;
                }
            }

            if ($type == null) {
                throw new \AddonModule\RealtimeRegisterSsl\addonLibs\exceptions\System(
                    'Unable to find provided column type: ('.implode(',',$options).')'
                );
            }
            
            $config = self::$predefinedColumTypes[$type]['default'];
            
            $config['charset'] = $charset;
                        
            foreach($data as $name => $value) {
                if ($value === true && isset($config[$name]) && is_numeric($config[$name])) {
                    // yyy to po cos mialo byc
                } elseif ($value) {
                    $config[$name] = $value;
                }
            }

            $definition = self::$predefinedColumTypes[$type]['definition'];

            $isNull = isset($config['null']) ? $config['null'] : false;
            
            $config['null'] = ($isNull)?'DEFAULT NULL':'NOT NULL';
            
            foreach($config as $name => $value) {
                $definition = str_replace(':'.$name.':', $value, $definition);
            }
            
            $definition = str_replace('^comma^', ',', $definition);

            if (!empty($config['isPrimaryKey'])) {
                $keys['primary'] = $config['name'];
            }
            
            if (!empty($config['isKey'])) {
                $keys['keys'][] = $config['name'];
            }
            
            if (!empty($config['uniqueKey'])) {
                $keys['uniqueKey'][] = $config['name'];
            }
            
            if (!empty($config['unique'])) {
                $keys['unique'][] = $config['name'];
            }
            
            if (!empty($config['refrence'])) {
                if (!isset($this->refrences[$tableName])) {
                    $this->refrences[$tableName] = [];
                }
                
                $this->refrences[$tableName][] = [
                    'column'        => $config['name'],
                    'refrence'     => $config['refrence'],
                    'ondelete'     => (empty($config['ondelete']))?'CASCADE':$config['ondelete'],
                    'onupdate'     => (empty($config['onupdate']))?'NO ACTION':$config['onupdate']
                ];
            }
            
            if (isset($existsColumns[$config['name']])) {
                if (
                    strpos($definition, $existsColumns[$config['name']]['Type']) === false
                    || ($isNull && $existsColumns[$config['name']]['Type'] == 'YES')
                    || (!$isNull && $existsColumns[$config['name']]['Type'] == 'NO')
               ) {
                    $updateColumns[$config['name']] = '`'.$config['name'].'` '.$definition;
                }
            } else {
                $addNewColumns[] = '`'.$config['name'].'` '.$definition;
            }
        }
        
        if (!in_array($tableName, $this->existsTables)) {
            if (!empty($keys['primary'])) {
                $addNewColumns[] = 'PRIMARY KEY (`'.$keys['primary'].'`)';
            }

            if (!empty($keys['keys'])) {
                foreach ($keys['keys'] as $key) {
                    $addNewColumns[] = 'KEY `'.$key.'` (`'.$key.'`)';
                }
            }
            
            if (!empty($keys['unique'])) {
                foreach($keys['unique'] as $key) {
                    $addNewColumns[] = 'UNIQUE KEY `'.$key.'` (`'.$key.'`)';
                }
            }
            
            if (!empty($keys['uniqueKey'])) {
                $addNewColumns[] = 'UNIQUE `unique_' . implode('_', $keys['uniqueKey']) . '` (`' . implode(
                        '`,`',
                        $keys['uniqueKey']
                    ) . '`)';
            }
        }
        
        if (in_array($tableName, $this->existsTables)) {
            foreach ($updateColumns as $column => $definition) {
                $sql = "ALTER TABLE `$tableName` CHANGE `$column` $definition";
                main\addonLibs\MySQL\Query::query($sql);
            }
            
            foreach ($addNewColumns as $definition) {
                $sql = "ALTER TABLE `$tableName`  ADD $definition";
                main\addonLibs\MySQL\Query::query($sql);
            }
        } else {
            $sql = 'CREATE TABLE `'.$tableName.'` (';
            $sql .= implode(",\n",$addNewColumns);      
            $sql .= ') ENGINE='.$engine.' DEFAULT CHARSET='.$charset;

            main\addonLibs\MySQL\Query::query($sql);
        }
    }
    
    function createRefrences()
    {
        foreach ($this->refrences as $table => $refrences) {
            $result = main\addonLibs\MySQL\Query::query("
                SHOW CREATE TABLE `".$table."`");
            
            $row = $result->fetch();
            
            $struct = $row['Create Table'];
            
            foreach ($refrences as $id => $refrence) {
                $refName = $table.'_ibfk_'.($id+1);
                $column = $refrence['column'];
                
                list($model,$refProperty) = explode('::',$refrence['refrence']);
                
                $class      = $this->mainNameSpace."\\".$model;
                $refColumn  = $class::getProperyColumn($refProperty);
                $refTable   = $class::tableName();
                                
                if (strpos($struct, $refName)) {
                    $sql = "
                        ALTER TABLE 
                            `".$table."` 
                        DROP FOREIGN KEY `$refName`;";
                    
                    main\addonLibs\MySQL\Query::query($sql);
                }
                
                $sql = "ALTER TABLE 
                            `$table`
                        ADD CONSTRAINT 
                            `$refName` 
                        FOREIGN KEY 
                            (`$column`) 
                        REFERENCES 
                            `$refTable` (`$refColumn`) 
                                ON DELETE ".$refrence['ondelete']." 
                                ON UPDATE ".$refrence['onupdate']." ;
                    
                ";
                
                main\addonLibs\MySQL\Query::query($sql);
            }
        }
    }
    
    function dropModels($models)
    {
        foreach ($models as $model) {
            $class = $this->mainNameSpace."\\".$model;
            $structure = $class::getTableStructure();
           
            $sql = "select COLUMN_NAME, CONSTRAINT_NAME, REFERENCED_COLUMN_NAME, REFERENCED_TABLE_NAME
                    from information_schema.KEY_COLUMN_USAGE
                    where TABLE_NAME = 'table to be checked';";
        }
    }
}
