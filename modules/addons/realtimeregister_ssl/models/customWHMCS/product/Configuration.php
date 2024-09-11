<?php

namespace AddonModule\RealtimeRegisterSsl\models\customWHMCS\product;

/**
 * @Table(name=custom_configuration)
 */
class Configuration extends \AddonModule\RealtimeRegisterSsl\mgLibs\models\Orm
{
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
