<?php

namespace MGModule\RealtimeRegisterSsl\models\customWHMCS\product;
use MGModule\RealtimeRegisterSsl as main;

/**
 * @Table(name=custom_configuration)
 */
class Configuration extends \MGModule\RealtimeRegisterSsl\mgLibs\models\Orm{
    /**
     * 
     * @Column(id)
     * @var type 
     */
    public $id;
    
    /**
     * @Column(varchar=32)
     * @var type 
     */
    public $name;
    
    /**
     * @Column(varchar=32)
     * @var type 
     */
    public $confa;
}