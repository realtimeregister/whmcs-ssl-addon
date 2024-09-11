<?php

/* * ********************************************************************
 * MGMF product developed. (2016-02-09)
 * *
 *
 *
 * This software is furnished under a license and may be used and copied
 * only  in  accordance  with  the  terms  of such  license and with the
 * inclusion of the above copyright notice.  This software  or any other
 * copies thereof may not be provided or otherwise made available to any
 * other person.  No title to and  ownership of the  software is  hereby
 * transferred.
 *
 *
 * ******************************************************************** */

namespace MGModule\RealtimeRegisterSsl\models\whmcs\admins;

/**
 * Description of Roles
 *
 * @Table(name=tbladminroles,preventUpdate,prefixed=false)
 */
class Role  extends \MGModule\RealtimeRegisterSsl\mgLibs\models\Orm
{
    /**
     *
     * @Column(id)
     * @var int
     */
    protected $id;
    
    /**
     *
     * @Column()
     * @var string 
     */
    protected $name;
    
    function getId()
    {
        return $this->id;
    }

    function getName()
    {
        return $this->name;
    }
}
