<?php
/* * ********************************************************************
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

namespace AddonModule\RealtimeRegisterSsl\models\whmcs\product;

/**
 * Description of repository
 *
 */
class ProductGroups extends \AddonModule\RealtimeRegisterSsl\addonLibs\models\Repository
{
    public function getModelClass()
    {
        return __NAMESPACE__.'\ProductGroup';
    }
    
     /**
     * @return ProductGroup[]
     */
    public function get()
    {
        return parent::get();
    }
}
