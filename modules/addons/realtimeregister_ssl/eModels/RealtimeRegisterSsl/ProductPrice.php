<?php

namespace AddonModule\RealtimeRegisterSsl\eModels\RealtimeRegisterSsl;

use AddonModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\KeyToIdMapping;

class ProductPrice
{
    public function saveToDatabase()
    {
        $productPriceRepo = new \AddonModule\RealtimeRegisterSsl\models\productPrice\Repository();

        $period = $this->getPeriodFromName($this->product);

        $productPriceRepo->onlyApiProductID(KeyToIdMapping::getIdByKey($this->getCleanProductName($this->product)))
            ->onlyPeriod((string)$period)
            ->onlyAction($this->action);

        if (!$productPriceRepo->count()) {
            $productPrice = new \AddonModule\RealtimeRegisterSsl\models\productPrice\ProductPrice();

            $productPrice->setApiProductID(KeyToIdMapping::getIdByKey($this->getCleanProductName($this->product)));
            $productPrice->setPeriod($period);
            $productPrice->setPrice($this->price);
            $productPrice->setAction($this->action);
            $productPrice->setCurrency($this->currency);

        } else {
            $priceRow = $productPriceRepo->fetchOne();

            $productPrice = new \AddonModule\RealtimeRegisterSsl\models\productPrice\ProductPrice($priceRow->getID());
            $productPrice->setPrice($this->price);
            $productPrice->setCurrency($this->currency);
        }
        $productPrice->save();
    }
    
    public function loadSavedPriceData($productID = null)
    {
        $productPriceRepo = new \AddonModule\RealtimeRegisterSsl\models\productPrice\Repository();
       
        if ($productID !== null) {
            $productPriceRepo->onlyApiProductID($productID);
        } else {
            $productPriceRepo->onlyApiProductID($this->id);
        }

        return $productPriceRepo->get();
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
