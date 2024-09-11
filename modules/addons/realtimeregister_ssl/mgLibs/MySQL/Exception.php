<?php

namespace AddonModule\RealtimeRegisterSsl\addonLibs\MySQL;

/**
 * MySQL Exception
 *
 */
class Exception extends \AddonModule\RealtimeRegisterSsl\addonLibs\exceptions\System
{
    private $_query;
    public function __construct($message, $query, $code = 0, $previous = null)
    {
        $this->_query = $query;
        $code = (int) $code;
        parent::__construct($message, $code, $previous);
    }
}
