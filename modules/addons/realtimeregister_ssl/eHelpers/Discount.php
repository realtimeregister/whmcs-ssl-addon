<?php

namespace AddonModule\RealtimeRegisterSsl\eHelpers;

use AddonModule\RealtimeRegisterSsl\models\userDiscount\UserDiscount;

class Discount
{

    public static function getDiscountValue($vars)
    {
        $productModel = new \AddonModule\RealtimeRegisterSsl\models\productConfiguration\Repository();

        $client = NULL;
        if (isset($_SESSION['uid']))
            $client = $_SESSION['uid'];

        if (isset($vars['client']))
            $client = $vars['client'];

        //get Realtime Register Ssl all products
        foreach ($productModel->getModuleProducts() as $product) {
            if ($product->id == $vars['pid']) {
                if ($client != NULL) {
                    $rules = UserDiscount::query()
                        ->where('client_id', '=', $client)
                        ->where('product_id', '=', $product->id)
                        ->first();

                    return $rules?->getPercentage() ?? 0;
                }
            }
        }

        return 0;
    }
}
