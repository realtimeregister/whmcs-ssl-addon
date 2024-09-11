<?php

namespace AddonModule\RealtimeRegisterSsl\mgLibs\exceptions;

/**
 * Use for general module errors
 *
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
