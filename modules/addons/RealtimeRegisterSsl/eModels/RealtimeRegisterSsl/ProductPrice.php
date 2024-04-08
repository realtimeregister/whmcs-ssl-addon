<?php

namespace MGModule\RealtimeRegisterSsl\eModels\RealtimeRegisterSsl;

use Exception;

class ProductPrice
{

    public function saveToDatabase()
    {

        $productPriceRepo = new \MGModule\RealtimeRegisterSsl\models\productPrice\Repository();
        $productPriceRepo->onlyApiProductID($this->id)->onlyPeriod($this->period);
        
        if (!$productPriceRepo->count())
        {
            $productPrice = new \MGModule\RealtimeRegisterSsl\models\productPrice\ProductPrice();

            $productPrice->setApiProductID($this->id);
            $productPrice->setPeriod($this->period);
            $productPrice->setPrice($this->price);
        }
        else
        {
            $priceRow = $productPriceRepo->fetchOne();

            $productPrice = new \MGModule\RealtimeRegisterSsl\models\productPrice\ProductPrice($priceRow->getID());
            $productPrice->setPrice($this->price);
        }
        $productPrice->save();
    }
    
    public function loadSavedPriceData($productID = NULL)
    {
        $productPriceRepo = new \MGModule\RealtimeRegisterSsl\models\productPrice\Repository();
       
        if($productID !== NULL)           
            $productPriceRepo->onlyApiProductID($productID);
        else
            $productPriceRepo->onlyApiProductID($this->id);

        
        return $productPriceRepo->get();
    }
}
