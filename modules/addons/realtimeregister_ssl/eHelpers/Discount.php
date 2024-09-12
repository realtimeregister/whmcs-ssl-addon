<?php

namespace AddonModule\RealtimeRegisterSsl\eHelpers;

use AddonModule\RealtimeRegisterSsl\models\userDiscount\Repository;

class Discount
{

    public static function getDiscountValue($vars)
    {
        $productModel = new \AddonModule\RealtimeRegisterSsl\models\productConfiguration\Repository();
        
        $client = NULL;
        if(isset($_SESSION['uid']))
            $client = $_SESSION['uid'];
        
        if(isset($vars['client']))
            $client = $vars['client'];
        
        //get Realtime Register Ssl all products
        foreach ($productModel->getModuleProducts() as $product)
        {
            if ($product->id == $vars['pid'])
            {
                if ($client != NULL)
                {
                    $commissionRepo = new Repository();
                    $rules = $commissionRepo->onlyClientID($client)
                        ->onlyProductID($product->id)
                        ->get();


                    if (!empty($rules))
                    {
                        return $rules[0]->getPercentage();
                    }
                }
            }
        }

        return 0;
    }
}
