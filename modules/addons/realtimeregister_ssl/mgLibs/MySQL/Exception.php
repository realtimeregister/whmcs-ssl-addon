<?php

namespace MGModule\RealtimeRegisterSsl\mgLibs\MySQL;

/**
 * MySQL Exception
 *
 */
class Exception extends \MGModule\RealtimeRegisterSsl\mgLibs\exceptions\System
{
    private $_query;
    public function __construct($message, $query, $code = 0, $previous = null)
    {
        $this->_query = $query;
        $code = (int) $code;
        parent::__construct($message, $code, $previous);
    }
}
