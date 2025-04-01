<?php

namespace AddonModule\RealtimeRegisterSsl\addonLibs\exceptions;

/**
 * Use for general module errors
 *
 */
class Validation extends System
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
