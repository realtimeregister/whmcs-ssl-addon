<?php

namespace AddonModule\RealtimeRegisterSsl\eModels\RealtimeRegisterSsl;

use AddonModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\KeyToIdMapping;
use AddonModule\RealtimeRegisterSsl\models\productPrice\ProductPrice;

class ProductPriceHelper
{
    public function saveToDatabase()
    {
        $period = $this->getPeriodFromName($this->product);

        $productPrice = ProductPrice::query()
            ->where("api_product_id", '=', KeyToIdMapping::getIdByKey($this->getCleanProductName($this->product)))
            ->where("period", '=', (string) $period)
            ->where("action", '=', $this->action)
            ->first();

        if ($productPrice) {
            $productPrice->setPrice($this->price);
        } else {
            $productPrice = new ProductPrice();
            $productPrice->setApiProductID(KeyToIdMapping::getIdByKey($this->getCleanProductName($this->product)));
            $productPrice->setPeriod($period);
            $productPrice->setPrice($this->price);
            $productPrice->setAction($this->action);
        }
        $productPrice->setCurrency($this->currency);
        $productPrice->save();
    }
    
    public function loadSavedPriceData($productID = null)
    {
        if ($productID !== null) {
            return ProductPrice::query()->where("api_product_id", '=', $productID);
        }
        return ProductPrice::query()->where("api_product_id", "=", $this->id);
    }

    private function getPeriodFromName(string $name): int
    {
        // default value
        $result = 12;
        if (strpos($name, '5years')) {
            $result = 60;
        } elseif (strpos($name, '4years')) {
            $result = 48;
        } elseif (strpos($name, '3years')) {
            $result = 36;
        } elseif (strpos($name, '2years')) {
            $result = 24;
        }
        return $result;
    }

    private function getCleanProductName(string $name): string
    {
        $period = $this->getPeriodFromName($name);

        if ($period !== 12) {
            $name = substr($name, 0, -7);
        }
        return $name;
    }
}
