<?php

namespace MGModule\RealtimeRegisterSsl\eHelpers;

class Commission
{

    public static function getCommissionValue($vars)
    {
        $productModel = new \MGModule\RealtimeRegisterSsl\models\productConfiguration\Repository();
        
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
                    $commissionRepo = new \MGModule\RealtimeRegisterSsl\models\userDiscount\Repository();
                    $rules = $commissionRepo->onlyClientID($client)
                        ->onlyProductID($product->id)
                        ->get();


                    if (!empty($rules))
                    {
                        $commission = $rules[0]->getPercentage();
                    }
                }
            }
        }

        return $commission;
    }
}
