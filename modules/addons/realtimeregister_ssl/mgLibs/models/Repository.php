<?php

namespace AddonModule\RealtimeRegisterSsl\addonLibs\models;
use AddonModule\RealtimeRegisterSsl as main;
use AddonModule\RealtimeRegisterSsl\addonLibs\MySQL\PdoWrapper;

/**
 * Description of abstractModel
 *
 * @SuppressWarnings(PHPMD)
 */
abstract class Repository
{
    protected $_filters = [];
    protected $_limit = null;
    protected $_offest = 0;
    protected $_order = [];
    
    abstract function getModelClass();
    
    public function __construct($columns = [], $search = [])
    {
        if (!empty($columns)) {
            $this->columns = $columns;
        }

        if (!empty($search)) {
            $this->search = $search;
        }
    }
    
    public function fieldDeclaration()
    {
        return forward_static_call([$this->getModelClass(),'fieldDeclaration']);
    }
    
    function getProperyColumn($property)
    {
        return forward_static_call([$this->getModelClass(),'getProperyColumn'],$property);
    }
    
    public function tableName()
    {
        return forward_static_call([$this->getModelClass(),'tableName']);
    }
    
    public function limit($limit)
    {
        $this->_limit = $limit;
    }
    
    public function offset($offset)
    {
        $this->_offest = $offset;
    }
    
    public function sortBy($field,$vect)
    {
        $column = forward_static_call([$this->getModelClass(),'getProperyColumn'],$field);
        $this->_order[$column] = $vect;
    }
    
    /**
     * 
     * @return orm
     */
    function get()
    {
        $result = main\addonLibs\MySQL\Query::select(
            self::fieldDeclaration(),
            self::tableName(),
            $this->_filters,
            $this->_order,
            $this->_limit,
            $this->_offest
        );

        $output = [];
        
        $class = $this->getModelClass();
        
        while ($row = $result->fetch()) {
            $output[] = new $class($row['id'],$row);
        }
        
        return $output;
    }
    
    function count()
    {
        $fields = $this->fieldDeclaration();
        $first = key($fields);
        
        if(is_numeric($first)) {
            $first = $fields[$first];
        }

        return main\addonLibs\MySQL\Query::count(
            $first,
            self::tableName(),
            $this->_filters,
            [],
            $this->_limit,
            $this->_offest
        );
    }
    
    function delete()
    {
        return main\addonLibs\MySQL\Query::delete(
            self::tableName(),
            $this->_filters
        );
    }
    
    /**
     * 
     * @param array $ids
     * @return \AddonModule\RealtimeRegisterSsl\addonLibs\models\Repository
     */
    public function idIn(array $ids)
    {
        foreach ($ids as &$id) {
            $id = (int)$id;
        }

        if (!empty($ids)) {
            $this->_filters['id'] = $ids;
        }

        return $this;
    }
    
    /**
     * 
     * @return Repository
     */
    public function resetFilters()
    {
        $this->_filters = [];
        $this->_order = [];
        $this->_limit = null;
        return $this;
    }
    
     /**
     * 
     * @return orm
     * @throws main\addonLibs\exceptions\System
     */
    public function fetchOne() {
  
        $result = main\addonLibs\MySQL\Query::select(
            self::fieldDeclaration(),
            self::tableName(),
            $this->_filters,
            $this->_order,
            1
        );
        
        $class = $this->getModelClass();
        $row = $result->fetch();        
        if (empty($row)) {
            $criteria = [];
            foreach ($this->_filters as $k => $v) {
                $criteria[] = "{$k}: $v";
            }
            $criteria = implode(", ", $criteria);
            throw new main\addonLibs\exceptions\System("Unable to find '{$class}' with criteria: ({$criteria}) ");
        }
        
        return new $class($row['id'], $row);
    }
    
    public function setSearch($search)
    {
        if (!$search) {
            return;
        }
        $search = PdoWrapper::realEscapeString($search);
        $filter = [];
        foreach ($this->search as $value) {
            $value = str_replace('?', $search, $value);
            $filter[] = "  $value ";
        }
        if (empty($filter)) {
            return false;
        }
        $sql = implode("OR", $filter);
        if ($sql) {
            $this->_filters[] = ' (' . $sql . ') ';
        }
    }
}
