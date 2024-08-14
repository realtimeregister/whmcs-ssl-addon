<?php

namespace MGModule\RealtimeRegisterSsl\mgLibs\exceptions;

/**
 * Use for general module errors
 *
 * @author Michal Czech <michael@modulesgarden.com>
 */
class validation extends System
{
    private $fields = [];

    public function __construct($message,array $fields = [])
    {
        $this->fields = $fields;
        parent::__construct($message);
    }
    
    function getFields()
    {
        return $this->fields;
    }
}
